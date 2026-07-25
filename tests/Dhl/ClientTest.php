<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Dhl\Auth;
use Kode\ExpressApi\Dhl\Client;
use Kode\ExpressApi\Dhl\Config;
use PHPUnit\Framework\TestCase;

class DhlClientTest extends TestCase
{
    private function makeConfig(array $over = []): Config
    {
        return new Config(array_merge([
            'app_key'         => 'test_api_key',
            'app_secret'      => 'test_api_secret',
            'account_number'  => '123456789',
            'sandbox'         => false,
        ], $over));
    }

    public function testConfigBaseUrl(): void
    {
        $this->assertSame('https://express.api.dhl.com/mydhlapi', $this->makeConfig()->getBaseUrl());
        $this->assertSame(
            'https://express.api.dhl.com/mydhlapi/test',
            $this->makeConfig(['sandbox' => true])->getBaseUrl()
        );
    }

    public function testAuthReturnsBasicToken(): void
    {
        $auth = new Auth($this->makeConfig());
        $token = $auth->getAccessToken();
        $this->assertStringStartsWith('Basic ', $token);
        $this->assertSame('Basic ' . base64_encode('test_api_key:test_api_secret'), $token);
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
}
