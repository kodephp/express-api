<?php

namespace Kode\ExpressApi\Yto;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 圆通速递（YTO）认证
 *
 * 圆通开放平台以「app_key + app_secret」签名方式鉴权（无独立 OAuth 令牌端点），
 * 签名由各客户端在请求头 / 请求体中按平台约定构造（见 Client::prepareRequestHeaders）。
 * 本类沿用统一认证基类的令牌缓存机制，真实令牌申请需网络，测试环境不触网。
 */
class Auth extends AbstractCourierAuth
{
    protected string $providerName = '圆通';
}
