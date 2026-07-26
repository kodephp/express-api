<?php

namespace Kode\ExpressApi\Ups;

use Kode\ExpressApi\Common\AbstractCourierAuth;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * UPS（联合包裹）认证
 *
 * UPS OAuth2 client_credentials 模式：向 {baseUrl}/security/v1/oauth/token 申请令牌，
 * 申请时用 HTTP Basic（Authorization = Basic base64(client_id:client_secret)）携带凭证，
 * body 仅含 grant_type=client_credentials。换得的 Bearer 令牌内存缓存并按过期时间刷新。
 *
 * 注：真实令牌需网络申请；测试环境（无凭证）下不触网，相关用例标记为 Incomplete。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = 'UPS';

    public function refreshToken(): string
    {
        $url = $this->config->getBaseUrl() . '/security/v1/oauth/token';

        $headers = [
            'Authorization' => 'Basic ' . base64_encode($this->config->getAppKey() . ':' . $this->config->getAppSecret()),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];

        $response = HttpClient::request(
            'POST',
            $url,
            ['grant_type' => 'client_credentials'],
            $headers,
            $this->config->getTimeout()
        );

        if (!isset($response['access_token'])) {
            throw new ExpressApiException(
                '获取UPS访问令牌失败: ' . ($response['error_description'] ?? '未知错误')
            );
        }

        $this->accessToken = (string) $response['access_token'];
        $expiresIn = (int) ($response['expires_in'] ?? 3600);
        $this->expiresAt = time() + $expiresIn - 60; // 提前 60 秒刷新
        $this->config->setAccessToken($this->accessToken);

        return $this->accessToken;
    }
}
