<?php

namespace Kode\ExpressApi\SF;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 顺丰速运 API 配置类
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
        return 'https://sfapi.sf-express.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://sfapi-sandbox.sf-express.com';
    }
}
