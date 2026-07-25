<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\FourPx\Auth;
use Kode\ExpressApi\FourPx\Config;
use PHPUnit\Framework\TestCase;

class FourPxAuthTest extends TestCase
{
    public function testAuthInitialization(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertInstanceOf(Auth::class, $auth);
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
