<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Jt\Auth;
use Kode\ExpressApi\Jt\Config;
use PHPUnit\Framework\TestCase;

class JtAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('极兔', $auth->getProviderName());
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
