<?php

namespace Kode\ExpressApi\Singpost;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * SingPost（新加坡邮政）认证
 *
 * SingPost API 以 API key 鉴权（请求头 Authorization: Bearer <apiKey>），
 * 无独立 OAuth 令牌端点。鉴权头由各客户端在 prepareRequestHeaders 中直接注入，
 * 本类沿用统一认证基类的 provider 标识与令牌缓存机制；真实鉴权不触网。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = 'SingPost';
}
