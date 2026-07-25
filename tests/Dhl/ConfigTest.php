<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Dhl\Config;
use PHPUnit\Framework\TestCase;

class DhlConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://express.api.dhl.com/mydhlapi', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://express.api.dhl.com/mydhlapi/test',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }

    public function testAccountNumber(): void
    {
        $config = new Config(['account_number' => 'ACC123']);
        $this->assertSame('ACC123', $config->getAccountNumber());
    }
}
