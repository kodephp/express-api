<?php

namespace Kode\ExpressApi\YunExpress;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 云途物流认证
 *
 * 云途采用 appKey + appToken，并以 HMAC-SHA256 签名鉴权，无需获取 OAuth 令牌。
 * 重写 refreshToken 不发起网络请求，appToken 作为身份凭据（实际签名在 Client 层完成）。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = '云途物流';

    public function refreshToken(): string
    {
        $this->accessToken = $this->config->getAppSecret();
        return $this->accessToken;
    }
}
