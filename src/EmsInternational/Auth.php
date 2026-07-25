<?php

namespace Kode\ExpressApi\EmsInternational;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 中国邮政 EMS 国际版认证
 *
 * EMS 国际采用 AppID + AppSecret 的 MD5 签名鉴权，无需获取 OAuth 令牌。
 * 重写 refreshToken 不发起网络请求，AppSecret 作为签名密钥（实际签名在 Client 层完成）。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = 'EMS国际';

    public function refreshToken(): string
    {
        $this->accessToken = $this->config->getAppSecret();
        return $this->accessToken;
    }
}
