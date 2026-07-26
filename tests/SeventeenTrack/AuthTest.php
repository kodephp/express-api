<?php

namespace Kode\ExpressApi\Tests\SeventeenTrack;

use Kode\ExpressApi\SeventeenTrack\Auth;
use Kode\ExpressApi\SeventeenTrack\Config;
use PHPUnit\Framework\TestCase;

/**
 * 17TRACK 认证测试
 */
class AuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_secret' => 'TOKEN']));
        $this->assertSame('17TRACK', $auth->getProviderName());
    }

    public function testAccessTokenIsAppSecret(): void
    {
        // 17TRACK 以 17token 鉴权，AccessToken 即 app_secret（避免误走 OAuth 取令牌流程）
        $auth = new Auth(new Config(['app_secret' => 'MY-TOKEN']));
        $this->assertSame('MY-TOKEN', $auth->getAccessToken());
    }
}
