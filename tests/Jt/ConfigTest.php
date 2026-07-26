<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Jt\Config;
use PHPUnit\Framework\TestCase;

class JtConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://openapi.jtexpress.com.cn/v1', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://openapi-uat.jtexpress.com.cn/v1',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersion(): void
    {
        $this->assertSame('v1', (new Config())->getVersion());
    }
}
