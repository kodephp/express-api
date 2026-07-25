<?php

namespace Kode\ExpressApi\SF;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 顺丰速运 API 认证类
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '顺丰';
}
