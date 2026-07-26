<?php

namespace Kode\ExpressApi\Jt;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 极兔速递（J&T）API 客户端
 *
 * 国内快递，继承统一客户端基类（HTTP 管道、配置 / 认证持有）。
 * 鉴权为「app_key + app_secret + 时间戳」签名，签名头在 prepareRequestHeaders 中构造。
 * 端点（以极兔开放平台为准，此处为骨架）：
 *  - 下单：POST /order/create
 *  - 订单查询：GET /order/query/{orderId}
 *  - 轨迹查询：GET /trace/query/{trackingNumber}
 *  - 取消：POST /order/cancel/{orderId}
 *  - 面单：POST /label/print/{orderId}
 */
class Client extends AbstractCourierClient
{
    protected function getProvider(): string
    {
        return 'jt';
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getAuthClass(): string
    {
        return Auth::class;
    }

    /**
     * 构造极兔签名请求头（app_key + timestamp + sign）
     *
     * 签名规则（骨架，以签约文档为准）：sign = md5(app_key + timestamp + app_secret)。
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        unset($headers['Authorization']);

        $timestamp = (string) time();
        $sign = md5($this->config->getAppKey() . $timestamp . $this->config->getAppSecret());

        $headers['X-JT-AppKey']   = $this->config->getAppKey();
        $headers['X-JT-Timestamp'] = $timestamp;
        $headers['X-JT-Sign']     = $sign;
        $headers['X-JT-Version']  = $this->config->getVersion();

        return $headers;
    }

    public function sendShipment(array $data): array
    {
        $this->validateShipmentData($data);
        return $this->request('POST', '/order/create', $data);
    }

    public function queryOrder(string $orderId): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('GET', '/order/query/' . $orderId);
    }

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

    public function printLabel(string $orderId, array $data = []): array
    {
        if (empty($orderId)) {
            throw new ExpressApiException('订单ID不能为空');
        }
        return $this->request('POST', '/label/print/' . $orderId, $data);
    }

    /**
     * 取件通知
     */
    public function pickupNotice(array $data): array
    {
        $this->validatePickupData($data);
        return $this->request('POST', '/pickup/create', $data);
    }

    /**
     * 拦截件
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
