<?php

namespace Kode\ExpressApi\Usps;

use Kode\ExpressApi\Common\AbstractCourierAuth;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * USPS（美国邮政）认证
 *
 * OAuth2 client_credentials：向 {baseUrl}/oauth/token 提交
 * grant_type=client_credentials + client_id + client_secret，换取 Bearer 令牌。
 * 令牌内存缓存并按 expires_in 提前过期刷新。
 *
 * 注：真实令牌需网络申请；测试环境（无凭证）下不触网，相关用例标记为 Incomplete。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = 'USPS';

    public function refreshToken(): string
    {
        $url = $this->config->getBaseUrl() . '/oauth/token';

        $response = HttpClient::request(
            'POST',
            $url,
            [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->config->getAppKey(),
                'client_secret' => $this->config->getAppSecret(),
            ],
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $this->config->getTimeout()
        );

        if (!isset($response['access_token'])) {
            throw new ExpressApiException(
                '获取 USPS 访问令牌失败: ' . ($response['error_description'] ?? '未知错误')
            );
        }

        $this->accessToken = (string) $response['access_token'];
        $expiresIn = (int) ($response['expires_in'] ?? 3600);
        $this->expiresAt = time() + $expiresIn - 60; // 提前 60 秒刷新
        $this->config->setAccessToken($this->accessToken);

        return $this->accessToken;
    }
}
