<?php

namespace Kode\ExpressApi\Yunda;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 韵达快递 API 认证类
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '韵达';
}
