<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\SfInternational\Auth;
use Kode\ExpressApi\SfInternational\Client;
use Kode\ExpressApi\SfInternational\Config;
use PHPUnit\Framework\TestCase;

class SfInternationalClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'       => 'test_app_key',
            'app_secret'    => 'test_app_secret',
            'customer_code' => 'TEST_CUSTOMER',
            'sandbox'       => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://openapi-portal.sf.global/api', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://openapi-portal.sf.global/sandbox/api',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testAuthHoldsConfigAndSecret(): void
    {
        $config = $this->makeConfig();
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // 顺丰国际为签名鉴权，accessToken 直接为 appSecret（不发起网络）
        $this->assertSame('test_app_secret', $auth->getAccessToken());
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
        $client->sendShipment([]);
    }

    public function testCreateAirFreightInjectsMode(): void
    {
        $client = new class($this->makeConfig()) extends Client {
            public $captured;
            public function sendShipment(array $data): array
            {
                $this->captured = $data;
                return ['ok' => true];
            }
        };

        $client->createAirFreight(['foo' => 'bar']);
        $this->assertSame('air', $client->captured['mode']);
    }
}
