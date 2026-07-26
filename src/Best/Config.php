<?php

namespace Kode\ExpressApi\Best;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 百世快递（百世汇通）API 配置
 *
 * 真实接入地址（百世开放平台）：
 *  - 生产：https://open.bestex.com
 *  - 测试：https://open-uat.bestex.com
 *
 * 鉴权为「app_key + app_secret」签名模式：每次请求携带时间戳并按约定规则生成签名。
 * 具体签名算法与令牌申请以签约后的百世开放平台文档为准，SDK 已提供签名骨架。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://open.bestex.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://open-uat.bestex.com';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
