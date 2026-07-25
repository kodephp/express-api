<?php

namespace Kode\ExpressApi\Cainiao;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 菜鸟网络API 客户端
 */
class Client extends AbstractCourierClient
{
    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'cainiao';
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
     * 菜鸟需要追加 X-Partner-Id 请求头
     *
     * @param array $headers
     * @return array
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        $headers['X-Partner-Id'] = $this->config->getPartnerId();

        return $headers;
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
        $this->validateOrderData($data);
        return $this->request('POST', '/order/create', $data);
    }

    /**
     * 下单（创建物流订单）
     *
     * @param array $data 订单数据
     * @return array
     * @throws ExpressApiException
     */
    public function createOrder(array $data): array
    {
        return $this->sendShipment($data);
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
            $this->validateOrderData($shipment);
        }
        return $this->request('POST', '/order/batch/create', ['orders' => $shipments]);
    }

    /**
     * 批量下单
     *
     * @param array $orders 订单数据数组
     * @return array
     * @throws ExpressApiException
     */
    public function batchCreateOrder(array $orders): array
    {
        return $this->batchSendShipment($orders);
    }

    /**
     * 获取支持的快递公司列表
     *
     * @return array
     * @throws ExpressApiException
     */
    public function getSupportedCouriers(): array
    {
        return $this->request('GET', '/courier/list');
    }

    /**
     * 电子面单打印
     *
     * @param array $data 打印数据
     * @return array
     * @throws ExpressApiException
     */
    public function printWaybill(array $data): array
    {
        if (!isset($data['order_ids']) || !isset($data['template_code'])) {
            throw new ExpressApiException('打印数据缺少必填字段: order_ids 或 template_code');
        }
        return $this->request('POST', '/waybill/print', $data);
    }

    /**
     * 获取电子面单余额
     *
     * @param string $courierCode 快递公司编码
     * @return array
     * @throws ExpressApiException
     */
    public function getWaybillBalance(string $courierCode): array
    {
        if (empty($courierCode)) {
            throw new ExpressApiException('快递公司编码不能为空');
        }
        return $this->request('GET', '/waybill/balance/' . $courierCode);
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
        // 对于菜鸟，需要从配置中获取默认的快递公司编码
        $courierCode = $this->config->getDefaultCourierCode();
        if (empty($courierCode)) {
            throw new ExpressApiException('缺少默认快递公司编码');
        }
        return $this->queryTrackingWithCourier($trackingNumber, $courierCode, $language);
    }

    /**
     * 查询物流轨迹（带快递公司编码）
     *
     * @param string $trackingNumber 运单号
     * @param string $courierCode 快递公司编码
     * @param string $language 语言（zh-CN, en-US）
     * @return array
     * @throws ExpressApiException
     */
    public function queryTrackingWithCourier(string $trackingNumber, string $courierCode, string $language = 'zh-CN'): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }
        if (empty($courierCode)) {
            throw new ExpressApiException('快递公司编码不能为空');
        }
        return $this->request('GET', '/tracking/query', [
            'tracking_number' => $trackingNumber,
            'courier_code' => $courierCode,
            'language' => $language
        ]);
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
     * 取件通知
     *
     * @param array $data 取件数据
     * @return array
     * @throws ExpressApiException
     */
    public function pickupNotice(array $data): array
    {
        $requiredFields = ['sender', 'receiver', 'order_ids', 'pickup_time'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("取件数据缺少必填字段: $field");
            }
        }
        return $this->request('POST', '/pickup/create', $data);
    }

    /**
     * 预约取件
     *
     * @param array $data 取件数据
     * @return array
     * @throws ExpressApiException
     */
    public function createPickup(array $data): array
    {
        return $this->pickupNotice($data);
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
            throw new ExpressApiException('修改数据不能为空');
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
        $printData = ['order_ids' => [$orderId]];

        // 如果提供了模板代码，使用它；否则使用默认模板
        if (!empty($data['template_code'])) {
            $printData['template_code'] = $data['template_code'];
        } else {
            $printData['template_code'] = $this->config->getDefaultTemplateCode();
        }

        // 合并其他打印参数
        $printData = array_merge($printData, $data);

        return $this->request('POST', '/waybill/print', $printData);
    }

    /**
     * 验证订单数据
     *
     * @param array $data
     * @throws ExpressApiException
     */
    protected function validateOrderData(array $data): void
    {
        $requiredFields = ['order_id', 'sender', 'receiver', 'items', 'courier_code'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("订单数据缺少必填字段: $field");
            }
        }
    }
}
