<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Royalmail\Client;
use Kode\ExpressApi\Royalmail\Config;
use PHPUnit\Framework\TestCase;

class RoyalmailClientTest extends TestCase
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
        $this->assertSame('https://api.royalmail.com', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://api-qa.royalmail.com',
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
}
