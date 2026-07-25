<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Yanwen\Config;
use PHPUnit\Framework\TestCase;

class YanwenConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://open.yw56.com.cn/api/order', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://open-fat.yw56.com.cn/api/order',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }

    public function testTrackingBaseUrl(): void
    {
        $this->assertSame('http://api.track.yw56.com.cn/api/tracking', (new Config())->getTrackingBaseUrl());
    }

    public function testUserIdAndApiToken(): void
    {
        $config = new Config(['app_key' => 'uid', 'app_secret' => 'token']);
        // 燕文客户号(user_id)复用 app_key，apitoken 复用 app_secret
        $this->assertSame('uid', $config->getUserId());
        $this->assertSame('token', $config->getApiToken());
    }
}
