<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Yto\Config;
use PHPUnit\Framework\TestCase;

class YtoConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://open.yto.net.cn/v1', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://open-uat.yto.net.cn/v1',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersion(): void
    {
        $this->assertSame('v1', (new Config())->getVersion());
    }
}
