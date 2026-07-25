<?php

namespace Kode\ExpressApi\DomesticFreight;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 国内货运通用操作默认实现（trait）
 *
 * ClientInterface 要求实现批量 / 拦截 / 改件 / 面单模板 / 揽收 / 网点 / 保价等一系列方法，
 * 但各家国内货运服务商对这些能力的支持程度不同。为避免 3 个具体 Client 重复编写
 * 大量「未实现」占位方法，这里统一提供默认实现：明确抛出「该服务商暂未实现此能力」，
 * 并在异常中提示应使用的能力。
 *
 * 具体 Client 通过 `use CommonDomesticFreightOperations;` 获得这些默认实现，
 * 再 override 真实支持的方法即可；类自身定义优先于 trait 方法。
 */
trait CommonDomesticFreightOperations
{
    public function pickupNotice(array $data): array
    {
        throw new ExpressApiException('该服务商暂未实现上门揽收预约能力，请改用 createLtl/createFtl 并自行预约');
    }

    public function batchSendShipment(array $shipments): array
    {
        throw new ExpressApiException('该服务商暂未实现批量下单能力，请改用 sendShipment 逐单提交');
    }

    public function batchQueryOrders(array $orderIds): array
    {
        throw new ExpressApiException('该服务商暂未实现批量订单查询能力');
    }

    public function batchQueryTracking(array $trackingNumbers, string $language = 'zh-CN'): array
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

    public function cargoInsurance(array $data): array
    {
        throw new ExpressApiException('该服务商暂未实现货物保价能力');
    }
}
