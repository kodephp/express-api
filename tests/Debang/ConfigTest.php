<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Debang\Config;
use PHPUnit\Framework\TestCase;

class DebangConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://open.deppon.com/v1', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://open-test.deppon.com/v1',
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
