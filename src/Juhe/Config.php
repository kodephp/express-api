<?php

namespace Kode\ExpressApi\Juhe;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 聚合数据 API 配置类（聚合查询）
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
        return 'https://v.juhe.cn';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://v.juhe.cn';
    }
}
