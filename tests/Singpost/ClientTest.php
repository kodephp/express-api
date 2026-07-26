<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Singpost\Client;
use Kode\ExpressApi\Singpost\Config;
use PHPUnit\Framework\TestCase;

class SingpostClientTest extends TestCase
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
        $this->assertSame('https://api.singpost.com/v1', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://sandbox-api.singpost.com/v1',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testClientExposesInternationalMethods(): void
    {
        $client = new Client($this->makeConfig());
        foreach (['sendShipment', 'queryOrder', 'queryTracking', 'cancelOrder', 'printLabel', 'getQuotation',
                     'declareCustoms', 'createSeaFreight', 'createAirFreight', 'pickupNotice', 'batchSendShipment',
                     'intercept', 'modify', 'batchPrintLabels', 'getLabelTemplate', 'queryCustoms'] as $m) {
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

    public function testPrepareRequestHeadersAddsBearerApiKey(): void
    {
        $client = new class($this->makeConfig()) extends Client {
            public function headers(): array
            {
                return $this->prepareRequestHeaders(['Content-Type' => 'application/json']);
            }
        };

        $h = $client->headers();
        $this->assertArrayHasKey('Authorization', $h);
        $this->assertStringStartsWith('Bearer test_api_key', $h['Authorization']);
    }
}
