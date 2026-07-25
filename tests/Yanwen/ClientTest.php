<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Yanwen\Auth;
use Kode\ExpressApi\Yanwen\Client;
use Kode\ExpressApi\Yanwen\Config;
use PHPUnit\Framework\TestCase;

class YanwenClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'    => 'test_user_id',
            'app_secret' => 'test_apitoken',
            'sandbox'    => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://open.yw56.com.cn/api/order', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://open-fat.yw56.com.cn/api/order',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testTrackingBaseUrl(): void
    {
        $this->assertSame('http://api.track.yw56.com.cn/api/tracking', $this->makeConfig()->getTrackingBaseUrl());
    }

    public function testAuthHoldsConfigAndUserId(): void
    {
        $config = $this->makeConfig();
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // 燕文为 MD5 签名鉴权，accessToken 直接为 apitoken（不发起网络）
        $this->assertSame('test_apitoken', $auth->getAccessToken());
        // 燕文客户号(user_id)复用 app_key
        $this->assertSame('test_user_id', $config->getUserId());
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

    public function testCreateSeaFreightInjectsMode(): void
    {
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
    }

    public function testRequireIdThrowsOnEmptyOrder(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->cancelOrder('');
    }
}
