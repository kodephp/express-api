<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Ups\Config;
use PHPUnit\Framework\TestCase;

class UpsConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://onlinetools.ups.com', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://wwwcie.ups.com',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }

    public function testAccountNumber(): void
    {
        $config = new Config(['account_number' => 'UPS123456']);
        $this->assertSame('UPS123456', $config->getAccountNumber());
    }
}
