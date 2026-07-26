<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Best\Client;
use Kode\ExpressApi\Best\Config;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use PHPUnit\Framework\TestCase;

class BestClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'    => 'test_api_key',
            'app_secret' => 'test_api_secret',
            'sandbox'    => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://open.bestex.com/v1', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://open-uat.bestex.com/v1',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testClientExposesExpressMethods(): void
    {
        $client = new Client($this->makeConfig());
        foreach (['sendShipment', 'pickupNotice', 'queryOrder', 'cancelOrder', 'queryTracking',
                     'intercept', 'modify', 'printLabel'] as $m) {
            $this->assertTrue(method_exists($client, $m), "missing method {$m}");
        }
    }

    public function testSendShipmentValidatesRequiredFields(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->sendShipment([]);
    }

    public function testQueryTrackingRejectsEmpty(): void
    {
        $client = new Client($this->makeConfig());
        $this->expectException(ExpressApiException::class);
        $client->queryTracking('');
    }

    public function testPrepareRequestHeadersAddsSignature(): void
    {
        $client = new class($this->makeConfig()) extends Client {
            public function headers(): array
            {
                return $this->prepareRequestHeaders(['Content-Type' => 'application/json']);
            }
        };

        $h = $client->headers();
        $this->assertArrayHasKey('X-Best-AppKey', $h);
        $this->assertArrayHasKey('X-Best-Timestamp', $h);
        $this->assertArrayHasKey('X-Best-Sign', $h);
        $this->assertSame('test_api_key', $h['X-Best-AppKey']);
        $this->assertArrayNotHasKey('Authorization', $h);
        // 签名应为 app_key + timestamp + app_secret 的 md5
        $this->assertSame(
            md5('test_api_key' . $h['X-Best-Timestamp'] . 'test_api_secret'),
            $h['X-Best-Sign']
        );
    }
}
