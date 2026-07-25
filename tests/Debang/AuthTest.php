<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Debang\Auth;
use Kode\ExpressApi\Debang\Config;
use PHPUnit\Framework\TestCase;

class DebangAuthTest extends TestCase
{
    public function testAuthInitialization(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertInstanceOf(Auth::class, $auth);
    }

    public function testGetAccessTokenReturnsEmptyForSignatureAuth(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        // 德邦为签名制鉴权，不依赖访问令牌
        $this->assertSame('', $auth->getAccessToken());
    }

    public function testClearTokenMethodExists(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertTrue(method_exists($auth, 'clearToken'));
    }

    public function testClearToken(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $auth->clearToken();
        $this->assertSame('', $config->getAccessToken());
    }
}
