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
}
