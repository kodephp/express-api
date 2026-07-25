<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\YunExpress\Config;
use PHPUnit\Framework\TestCase;

class YunExpressConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('http://oms.api.yunexpress.com', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'http://omsapi.uat.yunexpress.com',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }
}
