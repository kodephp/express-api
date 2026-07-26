<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Usps\Config;
use PHPUnit\Framework\TestCase;

class UspsConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.usps.com', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://api-test.usps.com',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        // 端点路径已含版本前缀（/track/v3），无需额外版本段
        $this->assertSame('', (new Config())->getVersion());
    }
}
