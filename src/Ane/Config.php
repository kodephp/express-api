<?php

namespace Kode\ExpressApi\Ane;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 安能物流配置
 *
 * 真实接入地址（安能开放平台）：
 *  - 生产：https://api.ane56.com
 *  - 测试：https://api-test.ane56.com
 *
 * 鉴权方式：app_key + digest（HMAC-SHA256）签名（详见 Ane\Auth / Client::sign）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.ane56.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api-test.ane56.com';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
