<?php

namespace Kode\ExpressApi\FourPx;

use Kode\ExpressApi\Common\AbstractCourierAuth;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * 4PX 递四方认证
 *
 * 4PX 采用 OAuth 授权获取 access_token（公共参数 access_token 使用），并配合 MD5 签名。
 * 这里复用抽象基类的令牌内存缓存与刷新骨架，仅重写 refreshToken 指向 4PX 的令牌端点。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = '4PX递四方';

    public function refreshToken(): string
    {
        // 4PX 通过 OAuth 授权方式获取 access_token；具体令牌端点以开放平台文档为准
        $url = $this->config->getBaseUrl() . '/oauth/token';

        $response = HttpClient::request(
            'POST',
            $url,
            [
                'grant_type' => 'client_credentials',
                'app_key'    => $this->config->getAppKey(),
                'app_secret' => $this->config->getAppSecret(),
            ],
            ['Content-Type' => 'application/json'],
            $this->config->getTimeout()
        );

        if (!isset($response['data']['access_token'])) {
            throw new ExpressApiException('获取4PX访问令牌失败: ' . ($response['msg'] ?? '无效的响应'));
        }

        $this->accessToken = (string) $response['data']['access_token'];
        $this->expiresAt = time() + 3600 - 300;

        return $this->accessToken;
    }
}
