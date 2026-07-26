<?php

namespace Kode\ExpressApi\Ups;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * UPS（联合包裹）API 配置
 *
 * 真实接入地址：
 *  - 生产：https://onlinetools.ups.com
 *  - 测试：https://wwwcie.ups.com
 *
 * 鉴权为 OAuth2 client_credentials，令牌申请需 Basic 头（client_id:client_secret）：
 * Authorization = Basic base64(client_id:client_secret)，body 为 grant_type=client_credentials。
 * 轨迹 / 下单 / 报价端点路径自带版本前缀（/api/track/v1 等），版本号留空。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://onlinetools.ups.com';
    }

    protected function getSandboxHost(): string
    {
        return 'https://wwwcie.ups.com';
    }

    public function getVersion(): string
    {
        // 各端点路径已含版本前缀（/api/track/v1 等），无需额外版本段
        return '';
    }

    /**
     * UPS 主账号（评级 / 下单必需）
     */
    public function getAccountNumber(): string
    {
        return $this->get('account_number', '');
    }
}
