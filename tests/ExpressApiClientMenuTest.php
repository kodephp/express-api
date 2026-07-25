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

        $expected = ['ems', 'sf', 'yunda', 'zto', 'sto', 'cainiao'];
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

        // EMS 应支持完整的面单与批量能力
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

        // Yunda 不支持批量轨迹查询与面单模板/批量打印
        $this->assertArrayNotHasKey('batchQueryTracking', $ops['query'] ?? []);
        $this->assertArrayNotHasKey('batchPrintLabels', $ops['label'] ?? []);
        $this->assertArrayNotHasKey('getLabelTemplate', $ops['label'] ?? []);

        // 但基础下单/查询/轨迹/面单应存在
        $this->assertArrayHasKey('sendShipment', $ops['order']);
        $this->assertArrayHasKey('queryOrder', $ops['query']);
        $this->assertArrayHasKey('printLabel', $ops['label']);
    }

    public function testCainiaoHasUniqueOperations(): void
    {
        $menu = ExpressApiClient::getApiMenu('cainiao');
        $ops = $menu['couriers']['cainiao']['operations'];

        // 菜鸟特有接口应被发现
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

        // 每个分类下的操作带有 label 元数据
        foreach ($ops as $category => $methods) {
            foreach ($methods as $method => $meta) {
                $this->assertArrayHasKey('label', $meta);
                $this->assertNotEmpty($meta['label']);
            }
        }
    }

    public function testGetApiMenuSingleCourier(): void
    {
        $menu = ExpressApiClient::getApiMenu('sf');
        $this->assertSame(['sf'], array_keys($menu['couriers']));
        $this->assertSame('顺丰速运', $menu['couriers']['sf']['name']);
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
    }
}
