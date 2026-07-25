<?php

namespace Kode\ExpressApi\Hoau;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\DomesticFreight\AbstractDomesticFreightClient;
use Kode\ExpressApi\DomesticFreight\CommonDomesticFreightOperations;

/**
 * 天地华宇（零担 / 整车 / 快运）API 客户端
 *
 * 真实接入：天地华宇开放平台，app_key + 时间戳 + MD5 签名；业务参数以 JSON 放请求体。
 * 端点（以开放平台文档为准）：
 *  - 下单：POST /gw/order/create
 *  - 查询：POST /gw/order/get
 *  - 轨迹：POST /gw/trace/query
 *  - 取消：POST /gw/order/cancel
 *  - 面单：POST /gw/order/print
 *  - 报价：POST /gw/price/query
 *  - 网点：POST /gw/network/query
 */
class Client extends AbstractDomesticFreightClient
{
    use CommonDomesticFreightOperations;

    protected function getProvider(): string
    {
        return 'hoau';
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
     * MD5 签名：app_key + 业务体(JSON 压缩) + timestamp + app_secret 拼接后取 32 位小写 MD5
     *
     * @param string $bodyJson
     * @return array 拼在 URL 后的公共鉴权参数
     */
    private function sign(string $bodyJson): array
    {
        $timestamp = (string) time();
        $raw       = $this->config->getAppKey() . $bodyJson . $timestamp . $this->config->getAppSecret();
        $sign      = md5($raw);

        return [
            'app_key'   => $this->config->getAppKey(),
            'timestamp' => $timestamp,
            'sign'      => $sign,
            'format'    => 'json',
        ];
    }

    /**
     * 统一调用天地华宇开放接口
     *
     * @param string $path
     * @param array  $bizData
     * @return array
     * @throws ExpressApiException
     */
    private function callHoau(string $path, array $bizData): array
    {
        $bodyJson = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $params   = $this->sign($bodyJson);
        $url      = $this->config->getBaseUrl() . $path . '?' . http_build_query($params);

        $response = $this->transmit('POST', $url, $bizData, ['Content-Type' => 'application/json']);

        if (($response['success'] ?? false) !== true && (int) ($response['code'] ?? -1) !== 0) {
            throw new ExpressApiException('天地华宇接口调用失败: ' . ($response['message'] ?? '未知错误'), 0, $response);
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

        return $this->callHoau('/gw/order/create', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callHoau('/gw/order/get', ['orderNo' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'zh-CN'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->callHoau('/gw/trace/query', ['waybillNo' => $trackingNumber]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callHoau('/gw/order/cancel', ['orderNo' => $orderId, 'reason' => $reason]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callHoau('/gw/order/print', ['orderNo' => $orderId]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callHoau('/gw/price/query', [
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
        return $this->callHoau('/gw/network/query', $data);
    }
}
