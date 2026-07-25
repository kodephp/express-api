<?php

namespace Kode\ExpressApi\Common;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * 快递公司通用认证基类（OAuth2 client_credentials 模式）
 *
 * 统一封装访问令牌的获取、内存缓存与过期刷新逻辑。各快递公司只需声明
 * {@see AbstractCourierAuth::$providerName}（用于错误信息展示）即可复用，
 * 不再各自维护一份几乎相同的认证实现。
 */
abstract class AbstractCourierAuth implements AuthInterface
{
    /**
     * 快递公司名称（用于错误信息展示）
     *
     * @var string
     */
    protected string $providerName = 'courier';

    /**
     * @var AbstractConfig 配置信息
     */
    protected AbstractConfig $config;

    /**
     * @var string|null 内存缓存的访问令牌
     */
    protected ?string $accessToken = null;

    /**
     * @var int|null 令牌过期时间戳
     */
    protected ?int $expiresAt = null;

    /**
     * 构造函数
     *
     * @param AbstractConfig $config 配置信息
     */
    public function __construct(AbstractConfig $config)
    {
        $this->config = $config;
        $token = $config->getAccessToken();
        $this->accessToken = $token === '' ? null : $token;
    }

    /**
     * 获取配置信息
     *
     * @return AbstractConfig
     */
    public function getConfig(): AbstractConfig
    {
        return $this->config;
    }

    /**
     * 获取访问令牌（自动处理获取 / 刷新）
     *
     * @return string
     * @throws ExpressApiException
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken === null || $this->isExpired()) {
            $this->refreshToken();
        }

        return $this->accessToken;
    }

    /**
     * 刷新访问令牌
     *
     * @return string 新的访问令牌
     * @throws ExpressApiException
     */
    public function refreshToken(): string
    {
        $url = $this->config->getBaseUrl() . '/auth/token';

        $data = [
            'app_key'    => $this->config->getAppKey(),
            'app_secret' => $this->config->getAppSecret(),
        ];

        // 菜鸟等需要 partner_id 的快递公司，配置中存在时自动附加
        $partnerId = $this->config->get('partner_id', '');
        if ($partnerId !== '') {
            $data['partner_id'] = $partnerId;
        }

        $response = HttpClient::request(
            'POST',
            $url,
            $data,
            ['Content-Type' => 'application/json'],
            $this->config->getTimeout()
        );

        if (!isset($response['data']['access_token'])) {
            throw new ExpressApiException(
                sprintf('获取%s访问令牌失败: 无效的响应', $this->providerName)
            );
        }

        $this->accessToken = (string) $response['data']['access_token'];
        $expiresIn = (int) ($response['data']['expires_in'] ?? 3600);
        $this->expiresAt = time() + $expiresIn - 300; // 提前 5 分钟刷新

        $this->config->setAccessToken($this->accessToken);

        return $this->accessToken;
    }

    /**
     * 检查令牌是否已过期
     *
     * @return bool
     */
    protected function isExpired(): bool
    {
        return $this->expiresAt !== null && time() >= $this->expiresAt;
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
        $this->config->setAccessToken(null);
    }

    /**
     * 获取令牌剩余有效期（秒）
     *
     * @return int
     */
    public function getRemainingTime(): int
    {
        if ($this->expiresAt === null) {
            return 0;
        }

        return max(0, $this->expiresAt - time());
    }
}
