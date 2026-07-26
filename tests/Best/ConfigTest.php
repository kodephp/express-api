<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Best\Config;
use PHPUnit\Framework\TestCase;

class BestConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://open.bestex.com/v1', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://open-uat.bestex.com/v1',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersion(): void
    {
        $this->assertSame('v1', (new Config())->getVersion());
    }
}
