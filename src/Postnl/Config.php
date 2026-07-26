<?php

namespace Kode\ExpressApi\Postnl;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * PostNL（荷兰邮政）API 配置
 *
 * 真实接入地址（PostNL API）：
 *  - 生产：https://api.postnl.nl
 *  - 测试：https://api-sandbox.postnl.nl
 *
 * 鉴权为 API key：请求头携带 apikey（由开放平台分配）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.postnl.nl';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api-sandbox.postnl.nl';
    }

    public function getVersion(): string
    {
        return '';
    }
}
