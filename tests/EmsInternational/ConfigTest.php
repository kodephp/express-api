<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\EmsInternational\Config;
use PHPUnit\Framework\TestCase;

class EmsInternationalConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.ems.com.cn/api', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://api.ems.com.cn/sandbox/api',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }
}
