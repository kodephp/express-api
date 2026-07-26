<?php

namespace Kode\ExpressApi\Royalmail;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * Royal Mail（英国皇家邮政）API 配置
 *
 * 真实接入地址（Royal Mail API）：
 *  - 生产：https://api.royalmail.com
 *  - 测试：https://api-qa.royalmail.com
 *
 * 鉴权为 OAuth2 client_credentials：向 /oauth/token 申请 Bearer 令牌。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://api.royalmail.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://api-qa.royalmail.com';
    }

    public function getVersion(): string
    {
        // 端点路径已含版本前缀（/shipping/v2），无需额外版本段
        return '';
    }
}
