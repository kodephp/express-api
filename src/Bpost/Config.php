<?php

namespace Kode\ExpressApi\Bpost;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * bpost（比利时邮政）API 配置
 *
 * 真实接入地址（bpost API）：
 *  - 生产：https://api.bpost.be
 *  - 测试：https://api.bpost.be（沙箱通过 X-ApiKey 区分，路径前缀 /xshipper）
 *
 * 鉴权为 API key：请求头携带 X-ApiKey。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.bpost.be';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api.bpost.be';
    }

    public function getVersion(): string
    {
        return '';
    }
}
