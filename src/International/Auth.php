<?php

namespace Kode\ExpressApi\International;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 国际物流认证
 *
 * 复用 OAuth2 client_credentials 令牌获取 / 缓存 / 刷新逻辑（抽象基类已实现），
 * 仅声明服务商名称用于错误信息展示。如需切换为签名鉴权等差异化方式，
 * 重写 refreshToken() / prepareRequestHeaders() 即可，无需改动业务方法。
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '国际物流';
}
