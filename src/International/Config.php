<?php

namespace Kode\ExpressApi\International;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 国际物流配置
 *
 * 统一接入：仅声明生产 / 沙箱 Host，URL 拼接、鉴权参数存取等全部复用抽象基类。
 */
class Config extends AbstractCourierConfig
{
    /**
     * 生产环境 Host（占位示例地址，接入真实服务时替换）
     *
     * @return string
     */
    protected function getProductionHost(): string
    {
        return 'https://api.international-logistics.example.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://api-sandbox.international-logistics.example.com';
    }
}
