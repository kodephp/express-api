<?php

namespace Kode\ExpressApi\Ane;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\DomesticFreight\AbstractDomesticFreightClient;
use Kode\ExpressApi\DomesticFreight\CommonDomesticFreightOperations;

/**
 * 安能物流（零担 / 整车 / 快运）API 客户端
 *
 * 真实接入：安能开放平台，app_key + digest（HMAC-SHA256）签名；业务参数以 JSON 放请求体。
 * 端点（以开放平台文档为准）：
 *  - 下单：POST /openapi/order/create
 *  - 查询：POST /openapi/order/get
 *  - 轨迹：POST /openapi/trace/query
 *  - 取消：POST /openapi/order/cancel
 *  - 面单：POST /openapi/order/print
 *  - 报价：POST /openapi/price/query
 *  - 网点：POST /openapi/network/query
 */
class Client extends AbstractDomesticFreightClient
{
    use CommonDomesticFreightOperations;

    protected function getProvider(): string
    {
        return 'ane';
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
     * HMAC-SHA256 签名：以 app_secret 为密钥，对 app_key + timestamp + 业务体(JSON 压缩) 取摘要
     *
     * @param string $bodyJson
     * @return array 拼在 URL 后的公共鉴权参数
     */
    private function sign(string $bodyJson): array
    {
        $timestamp = (string) time();
        $raw       = $this->config->getAppKey() . $timestamp . $bodyJson;
        $digest    = hash_hmac('sha256', $raw, $this->config->getAppSecret());

        return [
            'app_key'   => $this->config->getAppKey(),
            'timestamp' => $timestamp,
            'digest'    => $digest,
            'format'    => 'json',
        ];
    }

    /**
     * 统一调用安能开放接口
     *
     * @param string $path
     * @param array  $bizData
     * @return array
     * @throws ExpressApiException
     */
    private function callAne(string $path, array $bizData): array
    {
        $bodyJson = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $params   = $this->sign($bodyJson);
        $url      = $this->config->getBaseUrl() . $path . '?' . http_build_query($params);

        $response = $this->transmit('POST', $url, $bizData, ['Content-Type' => 'application/json']);

        $code = (int) ($response['code'] ?? -1);
        if ($code !== 0 && ($response['success'] ?? false) !== true) {
            throw new ExpressApiException('安能接口调用失败: ' . ($response['message'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    public function sendShipment(array $data): array
    {
        $this->validateFreightShipment($data);

        $serviceMap = [
            self::SERVICE_LTL    => 'LTL',
            self::SERVICE_FTL     => 'FTL',
            self::SERVICE_EXPRESS => 'EXPRESS',
        ];

        $payload = [
            'orderNo'     => $data['order_no'],
            'serviceType' => $serviceMap[$data['service_type']] ?? 'LTL',
            'sender'      => $data['sender'],
            'receiver'    => $data['receiver'],
            'goods'       => $data['goods'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
        ];

        return $this->callAne('/openapi/order/create', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callAne('/openapi/order/get', ['orderNo' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'zh-CN'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->callAne('/openapi/trace/query', ['waybillNo' => $trackingNumber]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callAne('/openapi/order/cancel', ['orderNo' => $orderId, 'reason' => $reason]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callAne('/openapi/order/print', ['orderNo' => $orderId]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callAne('/openapi/price/query', [
            'serviceType' => $data['service_type'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => $data['weight'],
        ]);
    }

    public function queryNetwork(array $data = []): array
    {
        foreach (['city', 'keyword'] as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("网点查询缺少必填字段: {$field}");
            }
        }
        return $this->callAne('/openapi/network/query', $data);
    }
}
