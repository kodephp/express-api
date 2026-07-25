<?php

namespace Kode\ExpressApi\Tests\International;

use Kode\ExpressApi\International\Config;
use Kode\ExpressApi\Common\AbstractConfig;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testConfigCreation()
    {
        $config = new Config([
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox' => true,
        ]);

        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(AbstractConfig::class, $config);
    }

    public function testGetAppKey()
    {
        $config = new Config(['app_key' => 'test_app_key', 'app_secret' => 'test_app_secret']);
        $this->assertEquals('test_app_key', $config->getAppKey());
    }

    public function testGetAppSecret()
    {
        $config = new Config(['app_key' => 'test_app_key', 'app_secret' => 'test_app_secret']);
        $this->assertEquals('test_app_secret', $config->getAppSecret());
    }

    public function testIsSandbox()
    {
        $config = new Config([
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox' => true,
        ]);
        $this->assertTrue($config->isSandbox());
    }

    public function testGetBaseUrlUsesSandboxHost()
    {
        $config = new Config([
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox' => true,
        ]);
        $this->assertEquals('https://api-sandbox.international-logistics.example.com/v1', $config->getBaseUrl());
    }

    public function testGetProductionUrlHasTrailingSlash()
    {
        $config = new Config([
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox' => false,
        ]);
        $this->assertEquals('https://api.international-logistics.example.com/v1/', $config->getProductionUrl());
    }
}
