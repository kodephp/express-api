<?php

namespace Kode\ExpressApi\Tests\Kuaidiniao;

use Kode\ExpressApi\Kuaidiniao\Auth;
use Kode\ExpressApi\Kuaidiniao\Config;
use PHPUnit\Framework\TestCase;

/**
 * 快递鸟 认证测试
 */
class AuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('快递鸟', $auth->getProviderName());
    }

    public function testGetConfigReturnsConfig(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
    }
}
