<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Hoau\Config;
use PHPUnit\Framework\TestCase;

class HoauConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.hoau.net/v1', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://api-test.hoau.net/v1',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersion(): void
    {
        $this->assertSame('v1', (new Config())->getVersion());
    }

    public function testAppKeySecretAccessors(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertSame('k', $config->getAppKey());
        $this->assertSame('s', $config->getAppSecret());
    }
}
