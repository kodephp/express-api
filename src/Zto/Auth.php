<?php

namespace Kode\ExpressApi\Zto;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 中通快递 API 认证类
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '中通';
}
