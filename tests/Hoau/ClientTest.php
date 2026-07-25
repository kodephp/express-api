<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\DomesticFreight\AbstractDomesticFreightClient;
use Kode\ExpressApi\Hoau\Client;
use Kode\ExpressApi\Hoau\Config;
use PHPUnit\Framework\TestCase;

class HoauClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'     => 'test_app_key',
            'app_secret'  => 'test_app_secret',
            'sandbox'     => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://api.hoau.net/v1', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://api-test.hoau.net/v1',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testClientExtendsDomesticFreightBase(): void
    {
        $client = new Client($this->makeConfig());
        $this->assertInstanceOf(AbstractDomesticFreightClient::class, $client);
    }

    public function testClientExposesDomesticFreightMethods(): void
    {
        $client = new Client($this->makeConfig());
        foreach (['sendShipment', 'queryOrder', 'queryTracking', 'cancelOrder', 'getQuotation',
                     'printLabel', 'createLtl', 'createFtl', 'queryNetwork',
                     'pickupNotice', 'intercept', 'modify',
                     'batchSendShipment', 'batchQueryOrders', 'batchQueryTracking',
                     'batchPrintLabels', 'getLabelTemplate', 'cargoInsurance'] as $m) {
            $this->assertTrue(method_exists($client, $m), "missing method {$m}");
        }
    }

    public function testSendShipmentValidatesRequiredFields(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->sendShipment([]); // 缺 service_type / 收发件 / 货物，校验层抛异常（不触网络）
    }

    public function testSendShipmentRequiresServiceType(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->sendShipment([
            'order_no'     => 'ORD123',
            'sender'       => ['name' => 'a', 'phone' => '1', 'address' => 'x'],
            'receiver'     => ['name' => 'b', 'phone' => '2', 'address' => 'y'],
            'goods'        => [['name' => 'g', 'weight' => 1]],
            'origin'       => '上海',
            'destination'  => '北京',
            // 故意不传 service_type
        ]);
    }

    public function testCreateLtlInjectsServiceType(): void
    {
        // 用匿名子类覆盖 sendShipment，验证便捷方法注入 service_type 且不触网络
        $client = new class($this->makeConfig()) extends Client {
            public $captured;
            protected function validateFreightShipment(array $data, bool $requireService = true): void
            {
                // 跳过真实校验，仅验证便捷入口注入
            }
            public function sendShipment(array $data): array
            {
                $this->captured = $data;
                return ['ok' => true];
            }
        };

        $client->createLtl(['foo' => 'bar']);
        $this->assertSame('ltl', $client->captured['service_type']);

        $client->createFtl(['foo' => 'bar']);
        $this->assertSame('ftl', $client->captured['service_type']);
    }

    public function testGetQuotationValidatesRequiredFields(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->getQuotation([]); // 缺 service_type / origin / destination / weight
    }

    public function testQueryOrderRequiresId(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->queryOrder(''); // 空订单号，校验层抛异常（不触网络）
    }

    public function testQueryTrackingRequiresId(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->queryTracking(''); // 空运单号
    }

    public function testQueryNetworkRequiresFields(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->queryNetwork([]); // 缺 city / keyword
    }

    public function testPickupNoticeNotSupportedByDefault(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->pickupNotice([]); // 该服务商默认未实现上门揽收
    }
}
