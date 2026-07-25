<?php

namespace Kode\ExpressApi\SfInternational;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 顺丰国际认证
 *
 * 顺丰国际新一代接口（IUOP）采用「appKey + 时间戳 + 业务报文 + appSecret」签名鉴权，
 * 无需获取 OAuth 令牌；签名密钥直接来自配置。因此重写 refreshToken 不发起任何网络请求，
 * 仅把 appSecret 作为身份凭据占位（实际签名在 Client 层完成）。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = '顺丰国际';

    public function refreshToken(): string
    {
        // 签名类鉴权：密钥直接来自配置，无需获取令牌
        $this->accessToken = $this->config->getAppSecret();
        return $this->accessToken;
    }
}
