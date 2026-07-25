<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\EmsInternational\Auth;
use Kode\ExpressApi\EmsInternational\Config;
use PHPUnit\Framework\TestCase;

class EmsInternationalAuthTest extends TestCase
{
    public function testAuthHoldsConfigAndSecret(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
        // EMS 国际为 MD5 签名鉴权，accessToken 直接为 AppSecret（不发起网络）
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
