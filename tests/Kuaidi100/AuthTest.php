<?php

namespace Kode\ExpressApi\Tests\Kuaidi100;

use Kode\ExpressApi\Kuaidi100\Auth;
use Kode\ExpressApi\Kuaidi100\Config;
use PHPUnit\Framework\TestCase;

/**
 * 快递100 认证测试
 */
class AuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('快递100', $auth->getProviderName());
    }

    public function testGetConfigReturnsConfig(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
    }
}
