<?php

namespace Kode\ExpressApi\Zto;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 中通快递API 客户端
 */
class Client extends AbstractCourierClient
{
    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'zto';
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
        return $this->request('POST', '/order/create', $data);
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
        return $this->request('POST', '/order/batch', ['orders' => $shipments]);
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
        return $this->request('POST', '/pickup/create', $data);
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
        return $this->request('GET', '/order/query/' . $orderId);
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
        return $this->request('POST', '/order/batch/query', ['order_ids' => $orderIds]);
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
        return $this->request('POST', '/order/cancel/' . $orderId, $data);
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
        return $this->request('GET', '/tracking/query/' . $trackingNumber, ['language' => $language]);
    }

    /**
     * 拦截订单
     *
     * @param string $orderId 订单ID
     * @param string $reason 拦截原因
     * @return array
     * @throws ExpressApiException
     */
    public function interceptOrder(string $orderId, string $reason): array
    {
        return $this->intercept($orderId, ['reason' => $reason]);
    }

    /**
     * 修改订单信息
     *
     * @param string $orderId 订单ID
     * @param array $updateData 更新数据
     * @return array
     * @throws ExpressApiException
     */
    public function updateOrderInfo(string $orderId, array $updateData): array
    {
        return $this->modify($orderId, $updateData);
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
        if (empty($data['reason'])) {
            throw new ExpressApiException('拦截原因不能为空');
        }
        return $this->request('POST', '/order/intercept', ['order_id' => $orderId, 'reason' => $data['reason']]);
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
            throw new ExpressApiException('更新数据不能为空');
        }
        return $this->request('PUT', '/order/update/' . $orderId, $data);
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
        $printData = ['order_id' => $orderId];

        // 添加可选参数
        if (!empty($data)) {
            $printData = array_merge($printData, $data);
        }

        return $this->request('POST', '/print/label', $printData);
    }

    /**
     * 验证发货数据
     *
     * @param array $data
     * @throws ExpressApiException
     */
    protected function validateShipmentData(array $data): void
    {
        $requiredFields = ['order_id', 'sender', 'receiver', 'items'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("发货数据缺少必填字段: $field");
            }
        }
    }

    /**
     * 验证取件数据
     *
     * @param array $data
     * @throws ExpressApiException
     */
    protected function validatePickupData(array $data): void
    {
        $requiredFields = ['order_ids', 'contact_name', 'contact_phone', 'pickup_address', 'pickup_time'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("取件数据缺少必填字段: $field");
            }
        }
    }
}
