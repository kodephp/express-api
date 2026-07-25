<?php

namespace Kode\ExpressApi\YunExpress;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * 云途物流（YunExpress）API 客户端
 *
 * 真实接入：云途 OMS 开放平台（oms.api.yunexpress.com）。
 * 鉴权使用 appKey + appToken，签名采用 HMAC-SHA256(timestamp + METHOD + path + body, appToken)。
 * 具体接口路径与字段以云途 OMS 开发规范（OMS 1.2.5）为准（本实现提供标准骨架，接入时核对）。
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'yunexpress';
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
     * 统一调用云途 OMS 接口（HMAC-SHA256 签名）
     */
    private function callYun(string $path, string $method, array $bizData): array
    {
        $bodyJson  = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $sign = hash_hmac('sha256', $timestamp . strtoupper($method) . $path . $bodyJson, $this->config->getAppSecret());

        $url = $this->config->getBaseUrl() . $path;
        $headers = [
            'Content-Type' => 'application/json',
            'appKey'       => $this->config->getAppKey(),
            'timestamp'    => $timestamp,
            'sign'         => $sign,
        ];

        $response = $this->transmit($method, $url, $bizData, $headers);

        if (($response['code'] ?? '00000') !== '00000') {
            throw new ExpressApiException('云途接口调用失败: ' . ($response['msg'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data);
        $payload = [
            'orderNo'            => $data['order_no'],
            'transportMode'      => $data['mode'] === 'air' ? 'AIR' : 'SEA',
            'destinationCountry' => $data['destination_country'],
            'sender'             => $data['sender'],
            'recipient'          => $data['recipient'],
            'items'              => $data['items'],
            'customs'            => [
                'hsCode'        => $data['hs_code'],
                'productName'   => $data['product_name'],
                'declaredValue' => $data['declared_value'],
                'currency'      => $data['currency'],
                'originCountry' => $data['origin_country'],
            ],
        ];
        return $this->callYun('/api/order/create', 'POST', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callYun('/api/order/query', 'POST', ['orderNo' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->callYun('/api/track/get', 'POST', ['trackNo' => $trackingNumber]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callYun('/api/order/cancel', 'POST', ['orderNo' => $orderId]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callYun('/api/rate/query', 'POST', [
            'mode'        => $data['mode'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => $data['weight'],
        ]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callYun('/api/label/print', 'POST', ['orderNo' => $orderId]);
    }

    public function declareCustoms(array $data): array
    {
        $this->validateCustomsData($data);
        return $this->callYun('/api/customs/declare', 'POST', $data);
    }
}
