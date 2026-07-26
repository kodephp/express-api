<?php

namespace Kode\ExpressApi\Common\Resolver;

/**
 * 聚合解析源接口
 *
 * 任何能够提供「运单号 → 外部承运商代码」识别能力的对象（聚合查询客户端、
 * 测试桩、第三方适配器）都可实现本接口，从而被 {@see AggregateResolver} 聚合，
 * 无需强耦合于具体的聚合查询客户端继承体系。
 */
interface ResolverSourceInterface
{
    /**
     * 识别运单号归属的承运商（外部代码）
     *
     * @param string $trackingNumber 运单号
     * @return array ['courier' => string, 'raw' => array]
     */
    public function recognizeTracking(string $trackingNumber): array;
}
