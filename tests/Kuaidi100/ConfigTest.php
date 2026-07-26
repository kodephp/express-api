<?php

namespace Kode\ExpressApi\Tests\Kuaidi100;

use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\Kuaidi100\Config;
use PHPUnit\Framework\TestCase;

/**
 * 快递100 配置测试
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
        $this->assertSame('https://poll.kuaidi100.com', $this->callProtected($config, 'getProductionHost'));
    }

    public function testBaseUrlUsesHost(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $this->assertStringStartsWith('https://poll.kuaidi100.com', $config->getBaseUrl());
    }

    public function testRegisteredInClient(): void
    {
        $client = ExpressApiClient::create('kuaidi100', ['app_key' => 'k', 'app_secret' => 's']);
        $this->assertInstanceOf(\Kode\ExpressApi\Kuaidi100\Client::class, $client);
    }
}
