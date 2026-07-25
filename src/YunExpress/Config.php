<?php

namespace Kode\ExpressApi\YunExpress;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 云途物流（YunExpress）配置
 *
 * 真实接入地址（云途 OMS 开放平台）：
 *  - 生产：http://oms.api.yunexpress.com
 *  - 测试：http://omsapi.uat.yunexpress.com
 *
 * 鉴权使用 appKey + appToken，并以 HMAC-SHA256(timestamp + method + path + body, appToken) 签名。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'http://oms.api.yunexpress.com';
    }

    protected function getSandboxHost(): string
    {
        return 'http://omsapi.uat.yunexpress.com';
    }

    public function getVersion(): string
    {
        return '';
    }
}
