<?php

namespace Kode\ExpressApi\SeventeenTrack;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 17TRACK API 认证类
 *
 * 17TRACK 使用单一的 17token 作为鉴权凭证，放在请求头 `17token` 中。
 * 这里以 app_secret 承载 17token（在 Client 中经 prepareRequestHeaders 注入）。
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '17TRACK';

    /**
     * 17TRACK 直接以 17token 鉴权，无需 OAuth 取令牌流程。
     * 为避免基类 refreshToken() 误请求 /auth/token，这里直接返回已配置的凭证。
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->config->getAppSecret();
    }
}
