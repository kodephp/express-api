<?php

namespace Kode\ExpressApi\Dhl;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * DHL Express（MyDHL API）配置
 *
 * 真实接入地址：
 *  - 生产：https://express.api.dhl.com/mydhlapi
 *  - 测试：https://express.api.dhl.com/mydhlapi/test
 *
 * 鉴权为 HTTP Basic：Authorization = Basic base64(apiKey:apiSecret)，无 OAuth 令牌刷新。
 * 另需 header：Accept / Message-Reference / Message-Reference-Date / x-version。
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://express.api.dhl.com/mydhlapi';
    }

    protected function getSandboxHost(): string
    {
        return 'https://express.api.dhl.com/mydhlapi/test';
    }

    public function getVersion(): string
    {
        // MyDHL API 路径已含 /mydhlapi，无需额外版本前缀
        return '';
    }

    /**
     * DHL 账号（寄件人结算账号，评级 / 创建运单必需）
     */
    public function getAccountNumber(): string
    {
        return $this->get('account_number', '');
    }
}
