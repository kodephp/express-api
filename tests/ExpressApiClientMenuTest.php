<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\ExpressApiClient;
use PHPUnit\Framework\TestCase;

/**
 * API 能力菜单（目录）测试
 */
class ExpressApiClientMenuTest extends TestCase
{
    public function testGetApiMenuReturnsAllCouriers(): void
    {
        $menu = ExpressApiClient::getApiMenu();

        $this->assertArrayHasKey('version', $menu);
        $this->assertSame(ExpressApiClient::VERSION, $menu['version']);
        $this->assertArrayHasKey('couriers', $menu);

        $expected = [
            'ems', 'sf', 'yunda', 'zto', 'sto', 'cainiao',
            'fourpx', 'sf_international', 'dhl', 'yunexpress', 'ems_international', 'yanwen',
            'debang', 'ane', 'hoau',
            'jd', 'kuaidi100', 'kuaidiniao', 'juhe',
        ];
        $this->assertSame($expected, array_keys($menu['couriers']));

        foreach ($menu['couriers'] as $courier) {
            $this->assertArrayHasKey('name', $courier);
            $this->assertArrayHasKey('operation_count', $courier);
            $this->assertArrayHasKey('operations', $courier);
            $this->assertGreaterThan(0, $courier['operation_count']);
        }
    }

    public function testEmsSupportsFullLabelOperations(): void
    {
        $menu = ExpressApiClient::getApiMenu('ems');
        $ops = $menu['couriers']['ems']['operations'];

        $this->assertArrayHasKey('batchQueryTracking', $ops['query']);
        $this->assertArrayHasKey('batchPrintLabels', $ops['label']);
        $this->assertArrayHasKey('getLabelTemplate', $ops['label']);
        $this->assertArrayHasKey('sendShipment', $ops['order']);
        $this->assertArrayHasKey('queryTracking', $ops['query']);
    }

    public function testYundaMissingBatchTrackingAndLabelTemplate(): void
    {
        $menu = ExpressApiClient::getApiMenu('yunda');
        $ops = $menu['couriers']['yunda']['operations'];

        $this->assertArrayNotHasKey('batchQueryTracking', $ops['query'] ?? []);
        $this->assertArrayNotHasKey('batchPrintLabels', $ops['label'] ?? []);
        $this->assertArrayNotHasKey('getLabelTemplate', $ops['label'] ?? []);

        $this->assertArrayHasKey('sendShipment', $ops['order']);
        $this->assertArrayHasKey('queryOrder', $ops['query']);
        $this->assertArrayHasKey('printLabel', $ops['label']);
    }

    public function testCainiaoHasUniqueOperations(): void
    {
        $menu = ExpressApiClient::getApiMenu('cainiao');
        $ops = $menu['couriers']['cainiao']['operations'];

        $this->assertArrayHasKey('createOrder', $ops['order']);
        $this->assertArrayHasKey('batchCreateOrder', $ops['order']);
        $this->assertArrayHasKey('createPickup', $ops['order']);
        $this->assertArrayHasKey('printWaybill', $ops['label']);
        $this->assertArrayHasKey('getWaybillBalance', $ops['label']);
        $this->assertArrayHasKey('queryTrackingWithCourier', $ops['query']);
    }

    public function testOperationsGroupedByCategory(): void
    {
        $menu = ExpressApiClient::getApiMenu('sf');
        $ops = $menu['couriers']['sf']['operations'];

        $this->assertArrayHasKey('order', $ops);
        $this->assertArrayHasKey('query', $ops);
        $this->assertArrayHasKey('label', $ops);

        foreach ($ops as $category => $methods) {
            foreach ($methods as $method => $meta) {
                $this->assertArrayHasKey('label', $meta);
                $this->assertNotEmpty($meta['label']);
            }
        }
    }

    public function testGetApiMenuSingleCourier(): void
    {
        $menu = ExpressApiClient::getApiMenu('dhl');
        $this->assertSame(['dhl'], array_keys($menu['couriers']));
        $this->assertSame('DHL国际', $menu['couriers']['dhl']['name']);
    }

    public function testGetApiMenuInvalidCourierThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ExpressApiClient::getApiMenu('not_a_courier');
    }

    public function testGetOperationCatalog(): void
    {
        $catalog = ExpressApiClient::getOperationCatalog();
        $this->assertArrayHasKey('sendShipment', $catalog);
        $this->assertArrayHasKey('queryTracking', $catalog);
        $this->assertSame('order', $catalog['sendShipment']['category']);
        $this->assertArrayHasKey('label', $catalog['sendShipment']);
        // 国际物流能力
        $this->assertArrayHasKey('createSeaFreight', $catalog);
        $this->assertSame('freight', $catalog['createSeaFreight']['category']);
        $this->assertArrayHasKey('declareCustoms', $catalog);
        $this->assertSame('customs', $catalog['declareCustoms']['category']);
    }

    public function testFourPxHasFreightAndCustomsOperations(): void
    {
        $menu = ExpressApiClient::getApiMenu('fourpx');
        $ops = $menu['couriers']['fourpx']['operations'];

        // 国际物流独有的海运 / 空运 / 报价能力
        $this->assertArrayHasKey('freight', $ops);
        $this->assertArrayHasKey('createSeaFreight', $ops['freight']);
        $this->assertArrayHasKey('createAirFreight', $ops['freight']);
        $this->assertArrayHasKey('getQuotation', $ops['freight']);

        // 海关申报 / 清关能力
        $this->assertArrayHasKey('customs', $ops);
        $this->assertArrayHasKey('declareCustoms', $ops['customs']);
        $this->assertArrayHasKey('queryCustoms', $ops['customs']);

        // 同时兼容标准下单（需指定运输方式）
        $this->assertArrayHasKey('sendShipment', $ops['order']);
    }

    public function testDomesticFreightHasLtlFtlAndNetworkOperations(): void
    {
        foreach (['debang', 'ane', 'hoau'] as $code) {
            $menu = ExpressApiClient::getApiMenu($code);
            $ops = $menu['couriers'][$code]['operations'];

            // 国内货运核心能力：下单 / 订单查询 / 轨迹 / 报价
            $this->assertArrayHasKey('sendShipment', $ops['order'] ?? []);
            $this->assertArrayHasKey('queryOrder', $ops['query'] ?? []);
            $this->assertArrayHasKey('queryTracking', $ops['query'] ?? []);
            // 货运专属：零担 / 整车 / 网点查询
            $this->assertArrayHasKey('freight', $ops);
            $this->assertArrayHasKey('createLtl', $ops['freight']);
            $this->assertArrayHasKey('createFtl', $ops['freight']);
            $this->assertArrayHasKey('queryNetwork', $ops['freight']);
        }
    }

    public function testAggregatorsExposeTrackingAndUnsupportedStubs(): void
    {
        foreach (['kuaidi100', 'kuaidiniao', 'juhe'] as $code) {
            $menu = ExpressApiClient::getApiMenu($code);
            $ops = $menu['couriers'][$code]['operations'];

            // 聚合查询核心能力：轨迹查询
            $this->assertArrayHasKey('queryTracking', $ops['query'] ?? []);
        }

        // 聚合商不承接实操：调用 sendShipment 会抛出「不支持」异常
        foreach (['kuaidi100', 'kuaidiniao', 'juhe'] as $code) {
            $client = ExpressApiClient::create($code, ['app_key' => 'k', 'app_secret' => 's']);
            try {
                $client->sendShipment([]);
                $this->fail("{$code} 应拒绝 sendShipment");
            } catch (\Kode\ExpressApi\Common\Exception\ExpressApiException $e) {
                $this->assertStringContainsString('不支持', $e->getMessage());
            }
        }
    }

    public function testJdExposesFullExpressOperations(): void
    {
        $menu = ExpressApiClient::getApiMenu('jd');
        $ops = $menu['couriers']['jd']['operations'];

        $this->assertArrayHasKey('sendShipment', $ops['order']);
        $this->assertArrayHasKey('queryOrder', $ops['query']);
        $this->assertArrayHasKey('queryTracking', $ops['query']);
        $this->assertArrayHasKey('cancelOrder', $ops['order']);
        $this->assertArrayHasKey('printLabel', $ops['label']);
    }

    public function testRecognizeAndBuildChainEntryPointsExist(): void
    {
        // 自动识别入口：仅凭运单号推断承运商
        $this->assertSame('sf', ExpressApiClient::recognize('SF1234567890123'));
        $this->assertSame('jd', ExpressApiClient::recognize('JD0091234567890'));
        $this->assertNull(ExpressApiClient::recognize('some-random-no'));

        // 自动编排入口：给定发货意图，自动组装物流链（无需逐段指定承运商）
        $chain = ExpressApiClient::buildChain(
            ['origin' => 'CN', 'dest' => 'US', 'weight' => 5, 'mode' => 'air'],
            ['sf' => [], 'debang' => [], 'dhl' => [], 'ems_international' => [], 'jd' => []]
        );
        $this->assertInstanceOf(\Kode\ExpressApi\LogisticsChain\LogisticsChain::class, $chain);
        $legs = $chain->toArray()['legs'];
        $this->assertCount(5, $legs); // 揽收 → 干线 → 跨境 → 清关 → 末端
    }
}
