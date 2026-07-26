<?php

namespace Kode\ExpressApi\Postnl;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * PostNL（荷兰邮政）认证
 *
 * PostNL API 以 API key 鉴权（请求头 apikey），无独立 OAuth 令牌端点。
 * 鉴权头由各客户端在 prepareRequestHeaders 中直接注入，本类沿用统一认证基类的
 * provider 标识与令牌缓存机制；真实鉴权不触网，测试环境不访问网络。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = 'PostNL';
}
