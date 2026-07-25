<?php

namespace Kode\ExpressApi\Hoau;

use Kode\ExpressApi\Common\AbstractCourierConfig;
use Kode\ExpressApi\Common\AuthInterface;

/**
 * 天地华宇认证（签名制，无需 Bearer Token）
 *
 * 天地华宇开放平台采用 app_key + 时间戳 + MD5 签名鉴权，不返回访问令牌。
 * Auth 仅实现 AuthInterface 契约：getAccessToken() 返回空串（由 Client 自行完成签名），
 * clearToken() 为空实现。
 */
class Auth implements AuthInterface
{
    protected AbstractCourierConfig $config;

    public function __construct(AbstractCourierConfig $config)
    {
        $this->config = $config;
    }

    public function getAccessToken(): string
    {
        return '';
    }

    public function clearToken(): void
    {
        // 签名制无令牌缓存，无需操作
    }
}
