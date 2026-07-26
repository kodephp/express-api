<?php

namespace Kode\ExpressApi\Jd;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 京东快递 / 京东物流 API 认证类
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '京东';
}
