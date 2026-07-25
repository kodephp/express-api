<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\YunExpress\Auth;
use Kode\ExpressApi\YunExpress\Config;
use PHPUnit\Framework\TestCase;

class YunExpressAuthTest extends TestCase
{
    public function testAuthHoldsConfigAndSecret(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // 云途为 HMAC 签名鉴权，accessToken 直接为 appToken（不发起网络）
        $this->assertSame('s', $auth->getAccessToken());
    }

    public function testClearToken(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $auth->clearToken();
        $this->assertSame('', $config->getAccessToken());
    }
}
