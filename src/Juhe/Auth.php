<?php

namespace Kode\ExpressApi\Juhe;

use Kode\ExpressApi\Common\AbstractCourierAuth;

/**
 * 聚合数据 API 认证类（聚合查询）
 */
class Auth extends AbstractCourierAuth
{
    /**
     * @var string
     */
    protected string $providerName = '聚合数据';
}
