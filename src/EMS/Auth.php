<?php

namespace Kode\ExpressApi\EMS;

use Kode\ExpressApi\Common\AuthInterface;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * EMS API 认证类
 */
class Auth implements AuthInterface
{
    /**
     * @var Config 配置信息
     */
    protected $config;

    /**
     * @var string 访问令牌
     */
    protected $accessToken;

    /**
     * @var int 令牌过期时间戳
     */
    protected $expiresAt;

    /**
     * 构造函数
     *
     * @param Config $config 配置信息
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 获取访问令牌
     *
     * @return string
     * @throws ExpressApiException
     */
    public function getAccessToken(): string
    {
        // 如果令牌未过期，直接返回
        if ($this->accessToken && $this->expiresAt > time()) {
            return $this->accessToken;
        }

        // 获取新的访问令牌
        $tokenData = $this->requestAccessToken();

        $this->accessToken = $tokenData['access_token'];
        $this->expiresAt = time() + $tokenData['expires_in'] - 60; // 提前60秒过期

        return $this->accessToken;
    }

    /**
     * 请求访问令牌
     *
     * @return array
     * @throws ExpressApiException
     */
    protected function requestAccessToken(): array
    {
        $url = $this->config->getBaseUrl() . '/auth/token';

        $postData = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->config->getAppKey(),
            'client_secret' => $this->config->getAppSecret(),
        ];

        $response = HttpClient::request(
            'POST',
            $url,
            $postData,
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $this->config->getTimeout()
        );

        if (!isset($response['access_token'])) {
            throw new ExpressApiException(
                '获取访问令牌失败: ' . ($response['message'] ?? '未知错误'),
                0,
                null,
                $response
            );
        }

        return $response;
    }

    /**
     * 清除当前令牌
     *
     * @return void
     */
    public function clearToken(): void
    {
        $this->accessToken = null;
        $this->expiresAt = null;
    }
}
