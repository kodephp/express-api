<?php

namespace Kode\ExpressApi\Tests\Jd;

use Kode\ExpressApi\Jd\Auth;
use Kode\ExpressApi\Jd\Config;
use PHPUnit\Framework\TestCase;

/**
 * 京东快递 / 京东物流 认证测试
 */
class AuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('京东', $auth->getProviderName());
    }

    public function testGetConfigReturnsConfig(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
    }

    public function testGetAccessTokenRequiresNetwork(): void
    {
        $this->markTestIncomplete('获取访问令牌需真实凭证，避免触网；仅验证配置注入');
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $auth->getAccessToken();
    }
}
