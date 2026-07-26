<?php

namespace Kode\ExpressApi\Jd;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 京东快递 / 京东物流 API 配置类
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
        return 'https://api.jdl.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://sandbox-api.jdl.com';
    }
}
