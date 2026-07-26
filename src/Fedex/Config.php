<?php

namespace Kode\ExpressApi\Fedex;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * FedEx（联邦快递）API 配置
 *
 * 真实接入地址：
 *  - 生产：https://apis.fedex.com
 *  - 测试：https://apis-sandbox.fedex.com
 *
 * 鉴权为 OAuth2 client_credentials：向 /oauth/token 申请 Bearer 令牌。
 * 轨迹 / 下单 / 报价端点路径已自带版本前缀（/track/v1、/ship/v1、/rate/v1），
 * 因此版本号留空，避免拼接多余斜杠。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://apis.fedex.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://apis-sandbox.fedex.com';
    }

    public function getVersion(): string
    {
        // 各端点路径已含版本前缀（/track/v1 等），无需额外版本段
        return '';
    }

    /**
     * FedEx 结算账号（评级 / 下单必需）
     */
    public function getAccountNumber(): string
    {
        return $this->get('account_number', '');
    }
}
