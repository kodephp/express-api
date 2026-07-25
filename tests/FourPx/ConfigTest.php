<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\FourPx\Config;
use PHPUnit\Framework\TestCase;

class FourPxConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://open.4px.com/router/api/service', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://open-test.4px.com/router/api/service',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }

    public function testAppKeySecretAccessors(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertSame('k', $config->getAppKey());
        $this->assertSame('s', $config->getAppSecret());
    }
}
