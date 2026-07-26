<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Jt\Client;
use Kode\ExpressApi\Jt\Config;
use PHPUnit\Framework\TestCase;

class JtClientTest extends TestCase
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
        $this->assertSame('https://openapi.jtexpress.com.cn/v1', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://openapi-uat.jtexpress.com.cn/v1',
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
        $this->assertArrayHasKey('X-JT-AppKey', $h);
        $this->assertArrayHasKey('X-JT-Timestamp', $h);
        $this->assertArrayHasKey('X-JT-Sign', $h);
        $this->assertSame('test_api_key', $h['X-JT-AppKey']);
        $this->assertArrayNotHasKey('Authorization', $h);
        // 签名应为 app_key + timestamp + app_secret 的 md5
        $this->assertSame(md5('test_api_key' . $h['X-JT-Timestamp'] . 'test_api_secret'), $h['X-JT-Sign']);
    }
}
