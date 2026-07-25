<?php

namespace Kode\ExpressApi\Tests\International;

use Kode\ExpressApi\International\Client;
use Kode\ExpressApi\International\Config;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $client;
    private $config;

    protected function setUp(): void
    {
        $this->config = new Config([
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox' => true,
        ]);

        $this->client = new Client($this->config);
    }

    public function testClientCreation()
    {
        $this->assertInstanceOf(Client::class, $this->client);
    }

    public function testGetConfig()
    {
        $this->assertSame($this->config, $this->client->getConfig());
    }

    /**
     * 标准统一接口方法应当全部存在
     */
    public function testStandardMethodsExist()
    {
        $methods = [
            'sendShipment', 'batchSendShipment', 'pickupNotice', 'queryOrder',
            'batchQueryOrders', 'cancelOrder', 'queryTracking', 'batchQueryTracking',
            'intercept', 'modify', 'printLabel', 'batchPrintLabels', 'getLabelTemplate',
        ];
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->client, $method), "缺少标准方法: {$method}");
        }
    }

    /**
     * 国际物流差异化方法应当存在
     */
    public function testInternationalMethodsExist()
    {
        $methods = ['createSeaFreight', 'createAirFreight', 'getQuotation', 'declareCustoms', 'queryCustoms'];
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->client, $method), "缺少国际物流方法: {$method}");
        }
    }

    /**
     * 下单缺少运输方式 mode 应在发请求前抛出
     */
    public function testSendShipmentRequiresMode()
    {
        $this->expectException(ExpressApiException::class);
        $this->client->sendShipment([
            'order_no' => 'O1',
            'sender' => ['name' => 'A', 'phone' => '1', 'address' => 'x'],
            'recipient' => ['name' => 'B', 'phone' => '2', 'address' => 'y'],
            'items' => [['sku' => 's']],
            'destination_country' => 'US',
            'hs_code' => '1234',
            'product_name' => 'Book',
            'declared_value' => 10,
            'currency' => 'USD',
            'origin_country' => 'CN',
        ]);
    }

    /**
     * 海运下单缺少海关申报要素应在发请求前抛出
     */
    public function testSeaFreightRequiresCustoms()
    {
        $this->expectException(ExpressApiException::class);
        $this->client->createSeaFreight([
            'order_no' => 'O1',
            'sender' => ['name' => 'A', 'phone' => '1', 'address' => 'x'],
            'recipient' => ['name' => 'B', 'phone' => '2', 'address' => 'y'],
            'items' => [['sku' => 's']],
            'destination_country' => 'US',
        ]);
    }

    /**
     * 运费报价缺少必填字段应在发请求前抛出
     */
    public function testGetQuotationRequiresFields()
    {
        $this->expectException(ExpressApiException::class);
        $this->client->getQuotation(['mode' => 'sea']);
    }

    /**
     * 海关申报缺少必填字段应在发请求前抛出
     */
    public function testDeclareCustomsRequiresFields()
    {
        $this->expectException(ExpressApiException::class);
        $this->client->declareCustoms(['hs_code' => '1234']);
    }

    /**
     * 清关查询缺少报关单号应在发请求前抛出
     */
    public function testQueryCustomsRequiresId()
    {
        $this->expectException(ExpressApiException::class);
        $this->client->queryCustoms('');
    }

    /**
     * 实际 API 调用测试（占位）
     */
    public function testApiCalls()
    {
        $this->markTestIncomplete('需要实现实际的API调用测试');
    }
}
