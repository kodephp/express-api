<?php

namespace Kode\ExpressApi\SfInternational;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 顺丰国际配置
 *
 * 真实接入地址（顺丰国际开放平台 openapi-portal.sf.global）：
 *  - 生产：https://openapi-portal.sf.global/api
 *  - 测试：https://openapi-portal.sf.global/sandbox/api
 *
 * 鉴权使用 appKey + appSecret + CustomerCode（客户编号）签名，具体接口路径与签名规则
 * 以签约后顺丰国际开放平台文档为准（本 SDK 已提供标准签名实现，可在此基础上核对）。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://openapi-portal.sf.global/api';
    }

    protected function getSandboxHost(): string
    {
        return 'https://openapi-portal.sf.global/sandbox/api';
    }

    public function getVersion(): string
    {
        return '';
    }

    /**
     * 顺丰国际客户编号（下单账号客户编号，非月结账号）
     */
    public function getCustomerCode(): string
    {
        return $this->get('customer_code', '');
    }
}
