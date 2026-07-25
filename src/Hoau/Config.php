<?php

namespace Kode\ExpressApi\Hoau;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 天地华宇配置
 *
 * 真实接入地址（天地华宇开放平台）：
 *  - 生产：https://api.hoau.net
 *  - 测试：https://api-test.hoau.net
 *
 * 鉴权方式：app_key + 时间戳 + MD5 签名（详见 Hoau\Auth / Client::sign）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.hoau.net';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api-test.hoau.net';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
