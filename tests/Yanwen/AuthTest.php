<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Yanwen\Auth;
use Kode\ExpressApi\Yanwen\Config;
use PHPUnit\Framework\TestCase;

class YanwenAuthTest extends TestCase
{
    public function testAuthHoldsConfigAndApiToken(): void
    {
        $config = new Config(['app_key' => 'uid', 'app_secret' => 'token']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // 燕文为 MD5 签名鉴权，accessToken 直接为 apitoken（不发起网络）
        $this->assertSame('token', $auth->getAccessToken());
    }

    public function testClearToken(): void
    {
        $config = new Config(['app_key' => 'uid', 'app_secret' => 'token']);
        $auth = new Auth($config);
        $auth->clearToken();
        $this->assertSame('', $config->getAccessToken());
    }
}
