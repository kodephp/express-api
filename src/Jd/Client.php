<?php

namespace Kode\ExpressApi\Jd;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 京东快递 / 京东物流 API 客户端
 */
class Client extends AbstractCourierClient
{
    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'jd';
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
     * 下单 / 发货
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function sendShipment(array $data): array
    {
        $this->validateShipmentData($data);
        return $this->request('POST', '/order/create', $data);
    }

    /**
     * 取件通知
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function pickupNotice(array $data): array
    {
        $this->validatePickupData($data);
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
     * 查询轨迹
     *
     * @param string $trackingNumber
     * @param string $language
     * @return array
     * @throws ExpressApiException
     */
    public function queryTracking(string $trackingNumber, string $language = 'zh-CN'): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }
        $uri = '/trace/query/' . $trackingNumber;
        if ($language) {
            $uri .= '?language=' . $language;
        }
        return $this->request('GET', $uri);
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
     * 面单打印
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
     * 验证发货数据
     *
     * @param array $data
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

        $contactRequiredFields = ['name', 'phone', 'address'];
        foreach (['sender', 'recipient'] as $contact) {
            foreach ($contactRequiredFields as $field) {
                if (!isset($data[$contact][$field])) {
                    throw new ExpressApiException("{$contact}缺少必填字段: {$field}");
                }
            }
        }

        if (!is_array($data['items']) || empty($data['items'])) {
            throw new ExpressApiException('商品信息不能为空');
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
        $requiredFields = ['pickup_time', 'sender', 'contact_person', 'contact_phone'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("取件数据缺少必填字段: {$field}");
            }
        }

        $contactRequiredFields = ['name', 'phone', 'address'];
        foreach ($contactRequiredFields as $field) {
            if (!isset($data['sender'][$field])) {
                throw new ExpressApiException("sender缺少必填字段: {$field}");
            }
        }
    }
}
