<?php

namespace Kode\ExpressApi\EmsInternational;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 中国邮政 EMS 国际版配置
 *
 * 真实接入地址（中国邮政 API 开发者平台 api.ems.com.cn）：
 *  - 生产：https://api.ems.com.cn/api
 *  - 测试：https://api.ems.com.cn/sandbox/api
 *
 * 鉴权使用 AppID（即 app_key）+ AppSecret，签名规则：MD5(AppID + RequestData + Timestamp + AppSecret)，
 * Sign 为大写。国际件需在业务数据中标识 CrossBorder 并携带 CustomsInfo（海关申报）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.ems.com.cn/api';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api.ems.com.cn/sandbox/api';
    }

    public function getVersion(): string
    {
        return '';
    }
}
