<?php

namespace Kode\ExpressApi\Jt;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 极兔速递（J&T）API 配置
 *
 * 真实接入地址（极兔开放平台）：
 *  - 生产：https://openapi.jtexpress.com.cn
 *  - 测试：https://openapi-uat.jtexpress.com.cn
 *
 * 鉴权为「app_key + app_secret」签名模式：每次请求携带时间戳并按约定规则生成签名。
 * 具体签名算法与令牌申请以签约后的极兔开放平台文档为准，SDK 已提供签名骨架。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://openapi.jtexpress.com.cn';
    }

    protected function getSandboxHost(): string
    {
        return 'https://openapi-uat.jtexpress.com.cn';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
