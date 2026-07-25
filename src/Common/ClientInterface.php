<?php

namespace Kode\ExpressApi\Common;

/**
 * 快递客户端接口
 */
interface ClientInterface
{
    /**
     * 发货通知
     *
     * @param array $data 发货数据
     * @return array
     */
    public function sendShipment(array $data): array;

    /**
     * 取件通知
     *
     * @param array $data 取件数据
     * @return array
     */
    public function pickupNotice(array $data): array;

    /**
     * 查询订单
     *
     * @param string $orderId 订单ID
     * @return array
     */
    public function queryOrder(string $orderId): array;

    /**
     * 取消订单
     *
     * @param string $orderId 订单ID
     * @param string $reason  取消原因
     * @return array
     */
    public function cancelOrder(string $orderId, string $reason = ''): array;

    /**
     * 查询轨迹
     *
     * @param string $trackingNumber 运单号
     * @param string $language        语言（zh-CN, en-US）
     * @return array
     */
    public function queryTracking(string $trackingNumber, string $language = 'zh-CN'): array;

    /**
     * 拦截件
     *
     * @param string $orderId 订单ID
     * @param array $data 拦截数据
     * @return array
     */
    public function intercept(string $orderId, array $data = []): array;

    /**
     * 改件信息
     *
     * @param string $orderId 订单ID
     * @param array $data 修改数据
     * @return array
     */
    public function modify(string $orderId, array $data): array;

    /**
     * 面单打印
     *
     * @param string $orderId 订单ID
     * @param array $data 打印数据
     * @return array
     */
    public function printLabel(string $orderId, array $data = []): array;
}
