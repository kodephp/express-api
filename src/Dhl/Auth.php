<?php

namespace Kode\ExpressApi\Dhl;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * DHL Express 认证
 *
 * MyDHL API 使用 HTTP Basic 鉴权：Authorization = Basic base64(apiKey:apiSecret)，
 * 没有 OAuth 令牌，也无需刷新。这里把 accessToken 直接构造为 Basic 串，供 Client 注入请求头。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = 'DHL';

    public function refreshToken(): string
    {
        $key = $this->config->getAppKey();
        $secret = $this->config->getAppSecret();
        $this->accessToken = 'Basic ' . base64_encode($key . ':' . $secret);
        return $this->accessToken;
    }
}
