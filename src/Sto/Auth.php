<?php

namespace Kode\ExpressApi\Sto;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 申通快递 API 认证类
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '申通';
}
