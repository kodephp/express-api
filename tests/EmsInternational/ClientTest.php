<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\EmsInternational\Auth;
use Kode\ExpressApi\EmsInternational\Client;
use Kode\ExpressApi\EmsInternational\Config;
use PHPUnit\Framework\TestCase;

class EmsInternationalClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'    => 'test_app_id',
            'app_secret' => 'test_app_secret',
            'sandbox'    => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://api.ems.com.cn/api', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://api.ems.com.cn/sandbox/api',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testAuthHoldsConfigAndSecret(): void
    {
        $config = $this->makeConfig();
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // EMS 国际为 MD5 签名鉴权，accessToken 直接为 AppSecret（不发起网络）
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

    public function testRequireIdThrowsOnEmptyTracking(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->queryTracking('');
    }
}
