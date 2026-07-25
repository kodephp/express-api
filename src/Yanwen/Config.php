<?php

namespace Kode\ExpressApi\Yanwen;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 燕文物流（YANWEN）配置
 *
 * 真实接入地址（燕文开放平台）：
 *  - 下单 / 查询：https://open.yw56.com.cn/api/order（生产） / https://open-fat.yw56.com.cn/api/order（测试）
 *  - 轨迹（独立域名）：http://api.track.yw56.com.cn/api/tracking
 *
 * 鉴权使用 user_id（客户号）+ apitoken，签名规则：
 *   MD5(apitoken + user_id + data + format + method + timestamp + version + apitoken)
 */
class Config extends AbstractCourierConfig
{
    protected function getProductionHost(): string
    {
        return 'https://open.yw56.com.cn/api/order';
    }

    protected function getSandboxHost(): string
    {
        return 'https://open-fat.yw56.com.cn/api/order';
    }

    public function getVersion(): string
    {
        return '';
    }

    /**
     * 轨迹查询独立域名
     */
    public function getTrackingBaseUrl(): string
    {
        return 'http://api.track.yw56.com.cn/api/tracking';
    }

    /**
     * 燕文客户号（即 user_id，下单接口公共参数使用）
     */
    public function getUserId(): string
    {
        return $this->getAppKey();
    }

    /**
     * 燕文 apitoken（签名密钥，存于 app_secret）
     */
    public function getApiToken(): string
    {
        return $this->getAppSecret();
    }
}
