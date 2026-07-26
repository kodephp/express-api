<?php

namespace Kode\ExpressApi\Tests\Jd;

use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\Jd\Config;
use PHPUnit\Framework\TestCase;

/**
 * 京东快递 / 京东物流 配置测试
 */
class ConfigTest extends TestCase
{
    private function callProtected(object $obj, string $method)
    {
        $r = new \ReflectionMethod($obj, $method);
        $r->setAccessible(true);
        return $r->invoke($obj);
    }

    public function testProductionHost(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertSame('https://api.jdl.com', $this->callProtected($config, 'getProductionHost'));
    }

    public function testSandboxHost(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertSame('https://sandbox-api.jdl.com', $this->callProtected($config, 'getSandboxHost'));
    }

    public function testBaseUrlUsesHost(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertStringStartsWith('https://api.jdl.com', $config->getBaseUrl());
    }

    public function testRegisteredInClient(): void
    {
        $client = ExpressApiClient::create('jd', ['app_key' => 'k', 'app_secret' => 's']);
        $this->assertInstanceOf(\Kode\ExpressApi\Jd\Client::class, $client);
    }
}
