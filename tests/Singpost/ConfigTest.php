<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Singpost\Config;
use PHPUnit\Framework\TestCase;

class SingpostConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.singpost.com/v1', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://sandbox-api.singpost.com/v1',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersion(): void
    {
        $this->assertSame('v1', (new Config())->getVersion());
    }
}
