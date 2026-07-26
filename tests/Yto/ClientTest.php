<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Yto\Client;
use Kode\ExpressApi\Yto\Config;
use PHPUnit\Framework\TestCase;

class YtoClientTest extends TestCase
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
        $this->assertSame('https://open.yto.net.cn/v1', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://open-uat.yto.net.cn/v1',
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
        $this->assertArrayHasKey('X-YTO-AppKey', $h);
        $this->assertArrayHasKey('X-YTO-Timestamp', $h);
        $this->assertArrayHasKey('X-YTO-Sign', $h);
        $this->assertSame('test_api_key', $h['X-YTO-AppKey']);
        $this->assertArrayNotHasKey('Authorization', $h);
        $this->assertSame(md5('test_api_key' . $h['X-YTO-Timestamp'] . 'test_api_secret'), $h['X-YTO-Sign']);
    }
}
