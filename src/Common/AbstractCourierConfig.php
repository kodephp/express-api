<?php

namespace Kode\ExpressApi\Common;

/**
 * 快递公司通用配置基类
 *
 * 统一封装 app_key / app_secret / access_token 的存取，以及沙箱 / 生产环境
 * 基础 URL 的拼接规则。各快递公司只需声明生产环境与沙箱环境的 Host，
 * 即可消除大量重复代码，并保证 URL 拼接触发一致、可预期。
 */
abstract class AbstractCourierConfig extends AbstractConfig
{
    /**
     * 默认配置
     *
     * @var array
     */
    protected $defaults = [
        'app_key'      => '',
        'app_secret'   => '',
        'access_token' => '',
        'sandbox'      => false,
        'timeout'      => 30,
        'version'      => 'v1',
    ];

    /**
     * 生产环境 Host（不含版本号与结尾斜杠）
     *
     * @return string
     */
    abstract protected function getProductionHost(): string;

    /**
     * 沙箱环境 Host（不含版本号与结尾斜杠）
     *
     * @return string
     */
    abstract protected function getSandboxHost(): string;

    /**
     * 获取基础 URL（含版本号，无结尾斜杠）
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        $host = $this->isSandbox() ? $this->getSandboxHost() : $this->getProductionHost();

        return rtrim($host, '/') . '/' . ltrim($this->getVersion(), '/');
    }

    /**
     * 获取沙箱环境 URL（含版本号与结尾斜杠）
     *
     * @return string
     */
    public function getSandboxUrl(): string
    {
        return rtrim($this->getSandboxHost(), '/') . '/' . ltrim($this->getVersion(), '/') . '/';
    }

    /**
     * 获取生产环境 URL（含版本号与结尾斜杠）
     *
     * @return string
     */
    public function getProductionUrl(): string
    {
        return rtrim($this->getProductionHost(), '/') . '/' . ltrim($this->getVersion(), '/') . '/';
    }

    /**
     * 获取应用 Key
     *
     * @return string
     */
    public function getAppKey(): string
    {
        return $this->get('app_key', '');
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
     * @param string|null $accessToken
     * @return self
     */
    public function setAccessToken(?string $accessToken): self
    {
        return $this->set('access_token', $accessToken ?? '');
    }
}
