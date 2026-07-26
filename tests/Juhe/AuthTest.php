<?php

namespace Kode\ExpressApi\Tests\Juhe;

use Kode\ExpressApi\Juhe\Auth;
use Kode\ExpressApi\Juhe\Config;
use PHPUnit\Framework\TestCase;

/**
 * 聚合数据 认证测试
 */
class AuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('聚合数据', $auth->getProviderName());
    }

    public function testGetConfigReturnsConfig(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
    }
}
