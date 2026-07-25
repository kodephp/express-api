<?php

namespace Kode\ExpressApi\Zto;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 中通快递 API 配置类
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
        return 'https://api-zto.kdniao.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://api-zto-sandbox.kdniao.com';
    }
}
