<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Dhl\Auth;
use Kode\ExpressApi\Dhl\Config;
use PHPUnit\Framework\TestCase;

class DhlAuthTest extends TestCase
{
    public function testReturnsBasicToken(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $token = $auth->getAccessToken();
        $this->assertStringStartsWith('Basic ', $token);
        $this->assertSame('Basic ' . base64_encode('k:s'), $token);
    }

    public function testAuthHoldsConfig(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $this->assertSame($config, $auth->getConfig());
    }

    public function testClearToken(): void
    {
        $config = new Config(['app_key' => 'k', 'app_secret' => 's']);
        $auth = new Auth($config);
        $auth->clearToken();
        $this->assertSame('', $config->getAccessToken());
    }
}
