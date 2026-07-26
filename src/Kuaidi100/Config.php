<?php

namespace Kode\ExpressApi\Kuaidi100;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 快递100 API 配置类（聚合查询）
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
        return 'https://poll.kuaidi100.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://poll.kuaidi100.com';
    }
}
