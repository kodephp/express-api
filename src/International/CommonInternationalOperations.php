<?php

namespace Kode\ExpressApi\International;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 国际物流通用操作默认实现（trait）
 *
 * ClientInterface 要求实现批量 / 拦截 / 改件 / 面单模板 / 揽收 / 清关等一系列方法，
 * 但各家国际物流服务商对这些能力的支持程度不同。为避免 6 个具体 Client 重复编写
 * 大量「未实现」占位方法，这里统一提供默认实现：明确抛出「该服务商暂未实现此能力」，
 * 并在异常中提示应使用的能力。
 *
 * 具体 Client 通过 `use CommonInternationalOperations;` 获得这些默认实现，
 * 再 override 真实支持的方法即可；类自身定义优先于 trait 方法。
 */
trait CommonInternationalOperations
{
    public function batchSendShipment(array $shipments): array
    {
        throw new ExpressApiException('该服务商暂未实现批量下单能力，请改用 sendShipment 逐单提交');
    }

    public function batchQueryOrders(array $orderIds): array
    {
        throw new ExpressApiException('该服务商暂未实现批量订单查询能力');
    }

    public function batchQueryTracking(array $trackingNumbers, string $language = 'en-US'): array
    {
        throw new ExpressApiException('该服务商暂未实现批量轨迹查询能力，请改用 queryTracking');
    }

    public function intercept(string $orderId, array $data = []): array
    {
        throw new ExpressApiException('该服务商暂未实现拦截件能力');
    }

    public function modify(string $orderId, array $data): array
    {
        throw new ExpressApiException('该服务商暂未实现改件信息能力');
    }

    public function batchPrintLabels(array $orderIds, array $data = []): array
    {
        throw new ExpressApiException('该服务商暂未实现批量面单打印能力');
    }

    public function getLabelTemplate(string $templateId): array
    {
        throw new ExpressApiException('该服务商暂未实现获取面单模板能力');
    }

    public function pickupNotice(array $data): array
    {
        throw new ExpressApiException('该服务商暂未实现揽收通知能力');
    }

    public function queryCustoms(string $declarationId): array
    {
        throw new ExpressApiException('该服务商暂未实现独立的清关状态查询能力，清关信息通常随下单 / 轨迹返回');
    }
}
