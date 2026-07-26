<?php

namespace Kode\ExpressApi\Kuaidiniao;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 快递鸟 API 认证类（聚合查询）
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '快递鸟';
}
