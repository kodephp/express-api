<?php

namespace Kode\ExpressApi\EMS;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * EMS API 客户端
 */
class Client extends AbstractCourierClient
{
    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'ems';
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
     * 发货通知
     *
     * @param array $data 发货数据
     * @return array
     * @throws ExpressApiException
     */
    public function sendShipment(array $data): array
    {
        // 验证必填字段
        $this->validateShipmentData($data);
        return $this->request('POST', '/shipment', $data);
    }

    /**
     * 批量发货通知
     *
     * @param array $shipments 发货数据数组
     * @return array
     * @throws ExpressApiException
     */
    public function batchSendShipment(array $shipments): array
    {
        foreach ($shipments as $shipment) {
            $this->validateShipmentData($shipment);
        }
        return $this->request('POST', '/shipment/batch', ['shipments' => $shipments]);
    }

    /**
     * 取件通知
     *
     * @param array $data 取件数据
     * @return array
     * @throws ExpressApiException
     */
    public function pickupNotice(array $data): array
    {
        // 验证必填字段
        $this->validatePickupData($data);
        return $this->request('POST', '/pickup', $data);
    }

    /**
     * 查询订单
     *
     * @param string $orderId 订单ID
     * @return array
     * @throws ExpressApiException
     */
    public function queryOrder(string $orderId): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('GET', '/order/' . $orderId);
    }

    /**
     * 批量查询订单
     *
     * @param array $orderIds 订单ID数组
     * @return array
     * @throws ExpressApiException
     */
    public function batchQueryOrders(array $orderIds): array
    {
        if (empty($orderIds)) {
            throw new ExpressApiException('订单ID列表不能为空');
        }
        return $this->request('POST', '/order/batch', ['order_ids' => $orderIds]);
    }

    /**
     * 取消订单
     *
     * @param string $orderId 订单ID
     * @param string $reason 取消原因
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
        return $this->request('DELETE', '/order/' . $orderId, $data);
    }

    /**
     * 查询轨迹
     *
     * @param string $trackingNumber 运单号
     * @param string $language 语言（zh-CN, en-US）
     * @return array
     * @throws ExpressApiException
     */
    public function queryTracking(string $trackingNumber, string $language = 'zh-CN'): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }
        $uri = '/tracking/' . $trackingNumber;
        if ($language) {
            $uri .= '?language=' . $language;
        }
        return $this->request('GET', $uri);
    }

    /**
     * 批量查询轨迹
     *
     * @param array $trackingNumbers 运单号数组
     * @param string $language 语言（zh-CN, en-US）
     * @return array
     * @throws ExpressApiException
     */
    public function batchQueryTracking(array $trackingNumbers, string $language = 'zh-CN'): array
    {
        if (empty($trackingNumbers)) {
            throw new ExpressApiException('运单号列表不能为空');
        }
        $data = ['tracking_numbers' => $trackingNumbers];
        if ($language) {
            $data['language'] = $language;
        }
        return $this->request('POST', '/tracking/batch', $data);
    }

    /**
     * 拦截件
     *
     * @param string $orderId 订单ID
     * @param array $data 拦截数据
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
     * @param string $orderId 订单ID
     * @param array $data 修改数据
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
     * 面单打印
     *
     * @param string $orderId 订单ID
     * @param array $data 打印数据
     * @return array
     * @throws ExpressApiException
     */
    public function printLabel(string $orderId, array $data = []): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('POST', '/order/' . $orderId . '/print', $data);
    }

    /**
     * 批量面单打印
     *
     * @param array $orderIds 订单ID数组
     * @param array $data 打印数据
     * @return array
     * @throws ExpressApiException
     */
    public function batchPrintLabels(array $orderIds, array $data = []): array
    {
        if (empty($orderIds)) {
            throw new ExpressApiException('订单ID列表不能为空');
        }
        $requestData = array_merge($data, ['order_ids' => $orderIds]);
        return $this->request('POST', '/order/batch/print', $requestData);
    }

    /**
     * 获取面单模板
     *
     * @param string $templateId 模板ID
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

    /**
     * 验证发货数据
     *
     * @param array $data 发货数据
     * @throws ExpressApiException
     */
    protected function validateShipmentData(array $data): void
    {
        $requiredFields = ['order_no', 'sender', 'recipient', 'items'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("发货数据缺少必填字段: {$field}");
            }
        }

        // 验证发件人和收件人信息
        $contactRequiredFields = ['name', 'phone', 'address'];
        foreach (['sender', 'recipient'] as $contact) {
            foreach ($contactRequiredFields as $field) {
                if (!isset($data[$contact][$field])) {
                    throw new ExpressApiException("{$contact}缺少必填字段: {$field}");
                }
            }
        }

        // 验证商品信息
        if (!is_array($data['items']) || empty($data['items'])) {
            throw new ExpressApiException('商品信息不能为空');
        }
    }

    /**
     * 验证取件数据
     *
     * @param array $data 取件数据
     * @throws ExpressApiException
     */
    protected function validatePickupData(array $data): void
    {
        $requiredFields = ['pickup_time', 'sender', 'contact_person', 'contact_phone'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("取件数据缺少必填字段: {$field}");
            }
        }

        // 验证发件人信息
        $contactRequiredFields = ['name', 'phone', 'address'];
        foreach ($contactRequiredFields as $field) {
            if (!isset($data['sender'][$field])) {
                throw new ExpressApiException("sender缺少必填字段: {$field}");
            }
        }
    }
}
