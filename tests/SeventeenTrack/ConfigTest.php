<?php

namespace Kode\ExpressApi\Tests\SeventeenTrack;

use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\SeventeenTrack\Config;
use PHPUnit\Framework\TestCase;

/**
 * 17TRACK 配置测试
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
        $config = new Config(['app_secret' => 'TOKEN']);
        $this->assertSame('https://api.17track.net', $this->callProtected($config, 'getProductionHost'));
    }

    public function testVersionIsEmpty(): void
    {
        // 17TRACK 接口路径自带前缀，版本号置空，避免拼接多余斜杠
        $config = new Config(['app_secret' => 'TOKEN']);
        $this->assertSame('', $config->getVersion());
        $this->assertStringEndsWith('api.17track.net', $config->getBaseUrl());
        $this->assertStringNotContainsString('api.17track.net/', $config->getBaseUrl());
    }

    public function testRegisteredInClient(): void
    {
        $client = ExpressApiClient::create('seventeentrack', ['app_secret' => 'TOKEN']);
        $this->assertInstanceOf(\Kode\ExpressApi\SeventeenTrack\Client::class, $client);
    }
}
