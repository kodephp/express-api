<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\FourPx\Auth;
use Kode\ExpressApi\FourPx\Client;
use Kode\ExpressApi\FourPx\Config;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use PHPUnit\Framework\TestCase;

class FourPxClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'    => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox'    => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://open.4px.com/router/api/service', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://open-test.4px.com/router/api/service',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testAuthHoldsConfig(): void
    {
        $config = $this->makeConfig();
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
    }

    public function testClientExtendsInternationalBase(): void
    {
        $client = new Client($this->makeConfig());
        $this->assertInstanceOf(\Kode\ExpressApi\International\AbstractInternationalClient::class, $client);
    }

    public function testClientExposesInternationalMethods(): void
    {
        $client = new Client($this->makeConfig());
        foreach (['sendShipment', 'queryOrder', 'queryTracking', 'cancelOrder', 'getQuotation',
                     'declareCustoms', 'printLabel', 'createSeaFreight', 'createAirFreight',
                     'pickupNotice', 'batchSendShipment', 'intercept', 'modify',
                     'batchPrintLabels', 'getLabelTemplate', 'queryCustoms'] as $m) {
            $this->assertTrue(method_exists($client, $m), "missing method {$m}");
        }
    }

    public function testSendShipmentValidatesRequiredFields(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->sendShipment([]); // 缺 mode / 海关字段，校验层抛异常（不触网络）
    }

    public function testCreateSeaFreightInjectsMode(): void
    {
        // 用匿名子类覆盖 sendShipment，验证便捷方法确实注入 mode 且不触网络
        $client = new class($this->makeConfig()) extends Client {
            public $captured;
            public function sendShipment(array $data): array
            {
                $this->captured = $data;
                return ['ok' => true];
            }
        };

        $client->createSeaFreight(['foo' => 'bar']);
        $this->assertSame('sea', $client->captured['mode']);

        $client->createAirFreight(['foo' => 'bar']);
        $this->assertSame('air', $client->captured['mode']);
    }
}
