<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Usps\Client;
use Kode\ExpressApi\Usps\Config;
use PHPUnit\Framework\TestCase;

class UspsClientTest extends TestCase
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
        $this->assertSame('https://api.usps.com', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://api-test.usps.com',
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
