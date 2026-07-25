<?php

namespace Kode\ExpressApi\Cainiao;

use Kode\ExpressApi\Common\AbstractCourierConfig;

/**
 * 菜鸟网络 API 配置类
 */
class Config extends AbstractCourierConfig
{
    /**
     * 默认配置
     *
     * @var array
     */
    protected $defaults = [
        'app_key'               => '',
        'app_secret'            => '',
        'access_token'          => '',
        'partner_id'            => '',
        'default_courier_code'  => '',
        'default_template_code' => '',
        'sandbox'               => false,
        'timeout'               => 30,
        'version'               => 'v1',
    ];

    /**
     * 生产环境 Host
     *
     * @return string
     */
    protected function getProductionHost(): string
    {
        return 'https://api-cainiao.openapi.alibaba.com';
    }

    /**
     * 沙箱环境 Host
     *
     * @return string
     */
    protected function getSandboxHost(): string
    {
        return 'https://api-cainiao-sandbox.openapi.alibaba.com';
    }

    /**
     * 获取合作伙伴 ID
     *
     * @return string
     */
    public function getPartnerId(): string
    {
        return $this->get('partner_id', '');
    }

    /**
     * 获取默认快递公司编码（用于轨迹查询）
     *
     * @return string
     */
    public function getDefaultCourierCode(): string
    {
        return $this->get('default_courier_code', '');
    }

    /**
     * 获取默认面单模板编码（用于面单打印）
     *
     * @return string
     */
    public function getDefaultTemplateCode(): string
    {
        return $this->get('default_template_code', '');
    }
}
