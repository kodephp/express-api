<?php

namespace Kode\ExpressApi\International;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 国际物流 API 客户端
 *
 * 继承统一的快递客户端基类，复用 HTTP 管道（鉴权头注入、SSL 校验、错误处理）。
 * 在基类之上扩展国际物流差异化能力：
 *  - 运输方式：海运（sea）/ 空运（air）
 *  - 海关申报：HS 编码、品名、申报价值、币种、原产地
 *  - 运费报价、清关状态查询
 * 标准接口方法（下单 / 查询 / 轨迹 / 取消 / 拦截 / 改件 / 面单）与国内快递保持统一，
 * 仅在校验层叠加国际必填项（运输方式、目的国、申报要素）。
 */
class Client extends AbstractCourierClient
{
    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'international';
    }

    /**
     * @return string
     */
    protected function getConfigClass(): string
    {
        return Config::class;
    }

    /**
     * @return string
     */
    protected function getAuthClass(): string
    {
        return Auth::class;
    }

    /**
     * 下单 / 发货（统一入口，按 mode 路由到海运或空运）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data);
        $mode = $data['mode'] === 'air' ? 'air' : 'sea';
        return $this->request('POST', '/shipment/' . $mode . '/create', $data);
    }

    /**
     * 批量下单
     *
     * @param array $shipments
     * @return array
     * @throws ExpressApiException
     */
    public function batchSendShipment(array $shipments): array
    {
        foreach ($shipments as $shipment) {
            $this->validateInternationalShipment($shipment);
        }
        return $this->request('POST', '/shipment/batch', ['shipments' => $shipments]);
    }

    /**
     * 起运地上门揽收（国际货运）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function pickupNotice(array $data): array
    {
        $required = ['pickup_address', 'contact_person', 'contact_phone', 'cargo'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("取件数据缺少必填字段: {$field}");
            }
        }
        return $this->request('POST', '/pickup/create', $data);
    }

    /**
     * 查询订单
     *
     * @param string $orderId
     * @return array
     * @throws ExpressApiException
     */
    public function queryOrder(string $orderId): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('GET', '/order/query/' . $orderId);
    }

    /**
     * 批量查询订单
     *
     * @param array $orderIds
     * @return array
     * @throws ExpressApiException
     */
    public function batchQueryOrders(array $orderIds): array
    {
        if (empty($orderIds)) {
            throw new ExpressApiException('订单ID列表不能为空');
        }
        return $this->request('POST', '/order/batch/query', ['order_ids' => $orderIds]);
    }

    /**
     * 取消订单
     *
     * @param string $orderId
     * @param string $reason
     * @return array
     * @throws ExpressApiException
     */
    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        $data = [];
        if (!empty($reason)) {
            $data['reason'] = $reason;
        }
        return $this->request('POST', '/order/cancel/' . $orderId, $data);
    }

    /**
     * 查询轨迹（国际物流默认英文回执）
     *
     * @param string $trackingNumber
     * @param string $language
     * @return array
     * @throws ExpressApiException
     */
    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }
        $uri = '/tracking/query/' . $trackingNumber;
        if ($language) {
            $uri .= '?language=' . $language;
        }
        return $this->request('GET', $uri);
    }

    /**
     * 批量查询轨迹
     *
     * @param array $trackingNumbers
     * @param string $language
     * @return array
     * @throws ExpressApiException
     */
    public function batchQueryTracking(array $trackingNumbers, string $language = 'en-US'): array
    {
        if (empty($trackingNumbers)) {
            throw new ExpressApiException('运单号列表不能为空');
        }
        $data = ['tracking_numbers' => $trackingNumbers];
        if ($language) {
            $data['language'] = $language;
        }
        return $this->request('POST', '/tracking/batch/query', $data);
    }

    /**
     * 拦截件
     *
     * @param string $orderId
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function intercept(string $orderId, array $data = []): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('POST', '/order/' . $orderId . '/intercept', $data);
    }

    /**
     * 改件信息
     *
     * @param string $orderId
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function modify(string $orderId, array $data): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        if (empty($data)) {
            throw new ExpressApiException('修改数据不能为空');
        }
        return $this->request('PUT', '/order/' . $orderId, $data);
    }

    /**
     * 面单打印（国际运单）
     *
     * @param string $orderId
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function printLabel(string $orderId, array $data = []): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('POST', '/label/print/' . $orderId, $data);
    }

    /**
     * 批量面单打印
     *
     * @param array $orderIds
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function batchPrintLabels(array $orderIds, array $data = []): array
    {
        if (empty($orderIds)) {
            throw new ExpressApiException('订单ID列表不能为空');
        }
        $requestData = array_merge($data, ['order_ids' => $orderIds]);
        return $this->request('POST', '/label/batch/print', $requestData);
    }

    /**
     * 获取面单模板
     *
     * @param string $templateId
     * @return array
     * @throws ExpressApiException
     */
    public function getLabelTemplate(string $templateId): array
    {
        if (empty($templateId)) {
            throw new ExpressApiException('模板ID不能为空');
        }
        return $this->request('GET', '/template/' . $templateId);
    }

    // ------------------------------------------------------------------
    // 国际物流差异化能力（继承基类后扩展）
    // ------------------------------------------------------------------

    /**
     * 海运下单（便捷入口，自动置 mode=sea）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function createSeaFreight(array $data): array
    {
        $data['mode'] = 'sea';
        return $this->sendShipment($data);
    }

    /**
     * 空运下单（便捷入口，自动置 mode=air）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function createAirFreight(array $data): array
    {
        $data['mode'] = 'air';
        return $this->sendShipment($data);
    }

    /**
     * 运费报价（按运输方式、起止地、重量 / 体积）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function getQuotation(array $data): array
    {
        $required = ['mode', 'origin', 'destination', 'weight'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ExpressApiException("运费报价缺少必填字段: {$field}");
            }
        }
        return $this->request('POST', '/quotation', $data);
    }

    /**
     * 海关申报（清关必备要素）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function declareCustoms(array $data): array
    {
        $required = ['hs_code', 'product_name', 'declared_value', 'currency', 'origin_country'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ExpressApiException("海关申报缺少必填字段: {$field}");
            }
        }
        return $this->request('POST', '/customs/declare', $data);
    }

    /**
     * 清关状态查询
     *
     * @param string $declarationId 报关单号
     * @return array
     * @throws ExpressApiException
     */
    public function queryCustoms(string $declarationId): array
    {
        if (empty($declarationId)) {
            throw new ExpressApiException('报关单号不能为空');
        }
        return $this->request('GET', '/customs/query/' . $declarationId);
    }

    // ------------------------------------------------------------------
    // 校验
    // ------------------------------------------------------------------

    /**
     * 国际物流下单校验（在统一必填项基础上叠加运输方式与海关申报要素）
     *
     * @param array $data
     * @throws ExpressApiException
     */
    protected function validateInternationalShipment(array $data): void
    {
        if (!isset($data['mode']) || !in_array($data['mode'], ['sea', 'air'], true)) {
            throw new ExpressApiException('国际物流下单必须指定运输方式 mode（sea 海运 / air 空运）');
        }

        $required = ['order_no', 'sender', 'recipient', 'items', 'destination_country'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("国际物流下单缺少必填字段: {$field}");
            }
        }

        // 海关申报要素（国际物流必填）
        $customsRequired = ['hs_code', 'product_name', 'declared_value', 'currency', 'origin_country'];
        foreach ($customsRequired as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("国际物流下单缺少海关申报字段: {$field}");
            }
        }

        // 收发货人基础信息
        $contactRequired = ['name', 'phone', 'address'];
        foreach (['sender', 'recipient'] as $contact) {
            foreach ($contactRequired as $field) {
                if (!isset($data[$contact][$field])) {
                    throw new ExpressApiException("{$contact}缺少必填字段: {$field}");
                }
            }
        }

        if (!is_array($data['items']) || empty($data['items'])) {
            throw new ExpressApiException('商品信息不能为空');
        }
    }
}
