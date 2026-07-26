<?php

namespace Kode\ExpressApi\Usps;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * USPS（美国邮政）API 配置
 *
 * 真实接入地址（USPS Web Tools / API）：
 *  - 生产：https://api.usps.com
 *  - 测试：https://api-test.usps.com
 *
 * 鉴权为 OAuth2 client_credentials：向 /oauth/token 申请 Bearer 令牌。
 * 轨迹端点路径自带版本前缀（/track/v3），因此版本号留空，避免拼接多余斜杠。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.usps.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api-test.usps.com';
    }

    public function getVersion(): string
    {
        // 各端点路径已含版本前缀（/track/v3 等），无需额外版本段
        return '';
    }
}
