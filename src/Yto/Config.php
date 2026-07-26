<?php

namespace Kode\ExpressApi\Yto;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 圆通速递（YTO）API 配置
 *
 * 真实接入地址（圆通开放平台）：
 *  - 生产：https://open.yto.net.cn
 *  - 测试：https://open-uat.yto.net.cn
 *
 * 鉴权为「app_key + app_secret」签名模式（典型为 md5(params + app_secret) 或平台约定算法），
 * 具体签名规则以签约后的圆通开放平台文档为准，SDK 已提供签名骨架。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://open.yto.net.cn';
    }

    protected function getSandboxHost(): string
    {
        return 'https://open-uat.yto.net.cn';
    }

    public function getVersion(): string
    {
        return 'v1';
    }
}
