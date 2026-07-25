<?php

namespace Kode\ExpressApi\Cainiao;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 菜鸟网络 API 认证类
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '菜鸟';
}
