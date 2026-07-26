<?php

namespace Kode\ExpressApi\Kuaidi100;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 快递100 API 认证类（聚合查询）
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '快递100';
}
