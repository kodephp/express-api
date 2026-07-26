<?php

namespace Kode\ExpressApi\Singpost;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * SingPost（新加坡邮政）API 配置
 *
 * 真实接入地址（SingPost API）：
 *  - 生产：https://api.singpost.com
 *  - 测试：https://sandbox-api.singpost.com
 *
 * 鉴权为 API key：请求头携带 Authorization: Bearer <apiKey>（或 apiKey 头）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.singpost.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://sandbox-api.singpost.com';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
