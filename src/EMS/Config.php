<?php

namespace Kode\ExpressApi\EMS;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * EMS API 配置类
 */
class Config extends AbstractCourierConfig
{
    /**
     * 生产环境 Host（不含版本号与结尾斜杠）
     *
     * @return string
     */
    protected function getProductionHost(): string
    {
        return 'https://api.ems.com.cn';
    }

    /**
     * 沙箱环境 Host（不含版本号与结尾斜杠）
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://api-sandbox.ems.com.cn';
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
