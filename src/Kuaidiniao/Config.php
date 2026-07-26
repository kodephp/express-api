<?php

namespace Kode\ExpressApi\Kuaidiniao;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 快递鸟 API 配置类（聚合查询）
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
        return 'https://api.kdniao.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://sandbox.kdniao.com';
    }
}
