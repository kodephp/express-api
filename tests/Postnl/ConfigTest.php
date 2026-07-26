<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Postnl\Config;
use PHPUnit\Framework\TestCase;

class PostnlConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.postnl.nl', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://api-sandbox.postnl.nl',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }
}
