<?php

namespace Kode\ExpressApi\Yanwen;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 燕文物流认证
 *
 * 燕文采用 user_id（客户号）+ apitoken 的 MD5 签名鉴权，无需获取 OAuth 令牌。
 * 重写 refreshToken 不发起网络请求，apitoken 作为签名密钥（实际签名在 Client 层完成）。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = '燕文物流';

    public function refreshToken(): string
    {
        $this->accessToken = $this->config->getAppSecret();
        return $this->accessToken;
    }
}
