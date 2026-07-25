<?php

namespace Kode\ExpressApi\Sto;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 申通快递 API 配置类
 */
class Config extends AbstractCourierConfig
{
    /**
     * 生产环境 Host
     *
     * @return string
     */
    protected function getProductionHost(): string
    {
        return 'https://api-sto.kdniao.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://api-sto-sandbox.kdniao.com';
    }
}
