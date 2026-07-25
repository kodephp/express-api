<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\SfInternational\Auth;
use Kode\ExpressApi\SfInternational\Config;
use PHPUnit\Framework\TestCase;

class SfInternationalAuthTest extends TestCase
{
    public function testAuthHoldsConfigAndSecret(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's', 'customer_code' => 'C']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // 顺丰国际为签名鉴权，accessToken 直接为 appSecret（不发起网络）
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
