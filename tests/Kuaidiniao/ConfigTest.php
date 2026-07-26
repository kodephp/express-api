<?php

namespace Kode\ExpressApi\Tests\Kuaidiniao;

use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\Kuaidiniao\Config;
use PHPUnit\Framework\TestCase;

/**
 * 快递鸟 配置测试
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
        $this->assertSame('https://api.kdniao.com', $this->callProtected($config, 'getProductionHost'));
    }

    public function testBaseUrlUsesHost(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertStringStartsWith('https://api.kdniao.com', $config->getBaseUrl());
    }

    public function testRegisteredInClient(): void
    {
        $client = ExpressApiClient::create('kuaidiniao', ['app_key' => 'k', 'app_secret' => 's']);
        $this->assertInstanceOf(\Kode\ExpressApi\Kuaidiniao\Client::class, $client);
    }
}
