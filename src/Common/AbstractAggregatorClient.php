<?php

namespace Kode\ExpressApi\Common;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 聚合查询客户端基类
 *
 * 快递100 / 快递鸟 / 聚合数据等聚合查询服务商只提供「轨迹查询 + 自动识别」，
 * 并不承接下单、打单、拦截等实操业务。本基类统一将 ClientInterface 中
 * 聚合商不支持的方法以明确异常抛出，仅保留 queryTracking（轨迹查询）
 * 与 recognizeTracking（运单号自动识别）作为必须实现的抽象方法，
 * 既满足接口契约，又避免各聚合商重复编写相同的「不支持」桩代码。
 */
abstract class AbstractAggregatorClient extends AbstractCourierClient
{
    /**
     * 发货通知（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function sendShipment(array $data): array
    {
        throw new ExpressApiException($this->notSupported('sendShipment'));
    }

    /**
     * 取件通知（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function pickupNotice(array $data): array
    {
        throw new ExpressApiException($this->notSupported('pickupNotice'));
    }

    /**
     * 查询订单（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function queryOrder(string $orderId): array
    {
        throw new ExpressApiException($this->notSupported('queryOrder'));
    }

    /**
     * 取消订单（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        throw new ExpressApiException($this->notSupported('cancelOrder'));
    }

    /**
     * 拦截件（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function intercept(string $orderId, array $data = []): array
    {
        throw new ExpressApiException($this->notSupported('intercept'));
    }

    /**
     * 改件信息（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function modify(string $orderId, array $data): array
    {
        throw new ExpressApiException($this->notSupported('modify'));
    }

    /**
     * 面单打印（聚合商不支持）
     *
     * @throws ExpressApiException
     */
    public function printLabel(string $orderId, array $data = []): array
    {
        throw new ExpressApiException($this->notSupported('printLabel'));
    }

    /**
     * 生成「不支持」异常文案
     *
     * @param string $op
     * @return string
     */
    protected function notSupported(string $op): string
    {
        return sprintf('聚合查询服务商 [%s] 不支持 %s；请使用具体快递商客户端进行实操业务。', $this->getProvider(), $op);
    }

    /**
     * 运单号自动识别（由各聚合商按自有接口实现）
     *
     * @param string $trackingNumber 运单号
     * @return array
     */
    abstract public function recognizeTracking(string $trackingNumber): array;
}
