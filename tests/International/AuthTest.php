<?php

namespace Kode\ExpressApi\Tests\International;

use Kode\ExpressApi\International\Auth;
use Kode\ExpressApi\International\Config;
use Kode\ExpressApi\Common\AuthInterface;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    private $auth;
    private $config;

    protected function setUp(): void
    {
        $this->config = new Config([
            'app_key' => 'test_app_key',
            'app_secret' => 'test_app_secret',
            'sandbox' => true,
        ]);

        $this->auth = new Auth($this->config);
    }

    public function testAuthCreation()
    {
        $this->assertInstanceOf(Auth::class, $this->auth);
        $this->assertInstanceOf(AuthInterface::class, $this->auth);
    }

    public function testGetConfig()
    {
        $this->assertSame($this->config, $this->auth->getConfig());
    }

    public function testClearToken()
    {
        $this->auth->clearToken();
        $this->assertSame('', $this->config->getAccessToken());
    }
}
