<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Fedex\Config;
use PHPUnit\Framework\TestCase;

class FedexConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://apis.fedex.com', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://apis-sandbox.fedex.com',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }

    public function testAccountNumber(): void
    {
        $config = new Config(['account_number' => 'FX123456']);
        $this->assertSame('FX123456', $config->getAccountNumber());
    }
}
