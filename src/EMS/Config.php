<?php

namespace Kode\ExpressApi\EMS;

use Kode\ExpressApi\Common\AbstractConfig;

/**
 * EMS API 配置类
 */
class Config extends AbstractConfig
{
    /**
     * 默认配置
     *
     * @var array
     */
    protected $defaults = [
        'app_key' => '',
        'app_secret' => '',
        'access_token' => '',
        'sandbox' => false,
        'timeout' => 30,
        'version' => 'v1',
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置参数
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
    }

    /**
     * 获取应用Key
     *
     * @return string
     */
    public function getAppKey(): string
    {
        return $this->get('app_key', '');
    }

    /**
     * 设置应用Key
     *
     * @param string $appKey 应用Key
     * @return self
     */
    public function setAppKey(string $appKey): self
    {
        return $this->set('app_key', $appKey);
    }

    /**
     * 获取应用密钥
     *
     * @return string
     */
    public function getAppSecret(): string
    {
        return $this->get('app_secret', '');
    }

    /**
     * 设置应用密钥
     *
     * @param string $appSecret 应用密钥
     * @return self
     */
    public function setAppSecret(string $appSecret): self
    {
        return $this->set('app_secret', $appSecret);
    }

    /**
     * 获取访问令牌
     *
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->get('access_token', '');
    }

    /**
     * 设置访问令牌
     *
     * @param string $token 访问令牌
     * @return self
     */
    public function setAccessToken(string $token): self
    {
        return $this->set('access_token', $token);
    }

    /**
     * 设置沙箱环境
     *
     * @param bool $sandbox 是否使用沙箱环境
     * @return self
     */
    public function setSandbox(bool $sandbox): self
    {
        return $this->set('sandbox', $sandbox);
    }

    /**
     * 设置超时时间
     *
     * @param int $timeout 超时时间
     * @return self
     */
    public function setTimeout(int $timeout): self
    {
        return $this->set('timeout', $timeout);
    }

    /**
     * 设置API版本
     *
     * @param string $version API版本
     * @return self
     */
    public function setVersion(string $version): self
    {
        return $this->set('version', $version);
    }

    /**
     * 获取基础URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->isSandbox() ?
            'https://api-sandbox.ems.com.cn/' . $this->getVersion() :
            'https://api.ems.com.cn/' . $this->getVersion();
    }

    /**
     * 获取沙箱环境URL
     *
     * @return string
     */
    public function getSandboxUrl(): string
    {
        return 'https://api-sandbox.ems.com.cn/' . $this->getVersion() . '/';
    }

    /**
     * 获取生产环境URL
     *
     * @return string
     */
    public function getProductionUrl(): string
    {
        return 'https://api.ems.com.cn/' . $this->getVersion() . '/';
    }

    /**
     * 验证配置是否完整
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !empty($this->getAppKey()) && !empty($this->getAppSecret());
    }

    /**
     * 获取调试信息
     *
     * @return array
     */
    public function getDebugInfo(): array
    {
        return [
            'version' => $this->getVersion(),
            'sandbox' => $this->isSandbox(),
            'timeout' => $this->getTimeout(),
            'base_url' => $this->getBaseUrl(),
            'has_app_key' => !empty($this->getAppKey()),
            'has_app_secret' => !empty($this->getAppSecret()),
            'has_access_token' => !empty($this->getAccessToken()),
        ];
    }
}
