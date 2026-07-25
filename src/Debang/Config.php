<?php

namespace Kode\ExpressApi\Debang;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 德邦物流配置
 *
 * 真实接入地址（德邦开放平台）：
 *  - 生产：https://open.deppon.com
 *  - 测试：https://open-test.deppon.com
 *
 * 鉴权方式：app_key + 时间戳 + MD5 签名（详见 Debang\Auth / Client::sign）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://open.deppon.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://open-test.deppon.com';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
