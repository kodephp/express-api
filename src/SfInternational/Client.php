<?php

namespace Kode\ExpressApi\SfInternational;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * 顺丰国际 API 客户端
 *
 * 真实接入：顺丰国际开放平台 openapi-portal.sf.global。
 * 鉴权使用 appKey + appSecret + CustomerCode（客户编号），签名采用
 * HMAC-SHA256(timestamp + METHOD + path + body, appSecret)。
 * 具体接口路径与签名规则以签约后顺丰国际开放平台文档为准（本实现提供标准骨架，接入时核对）。
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'sf_international';
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
     * 统一调用顺丰国际接口（HMAC-SHA256 签名）
     */
    private function callSf(string $path, string $method, array $bizData): array
    {
        $bodyJson  = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $sign = hash_hmac('sha256', $timestamp . strtoupper($method) . $path . $bodyJson, $this->config->getAppSecret());

        $url = $this->config->getBaseUrl() . $path;
        $headers = [
            'Content-Type'  => 'application/json',
            'appKey'        => $this->config->getAppKey(),
            'timestamp'     => $timestamp,
            'sign'          => $sign,
            'CustomerCode'  => $this->config->getCustomerCode(),
        ];

        $response = $this->transmit($method, $url, $bizData, $headers);

        if (($response['success'] ?? true) === false || (isset($response['code']) && $response['code'] != 200)) {
            throw new ExpressApiException('顺丰国际接口调用失败: ' . ($response['message'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data);
        $payload = [
            'orderNo'            => $data['order_no'],
            'serviceCode'        => $data['mode'] === 'air' ? 'IECS' : 'IECG',
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
        return $this->callSf('/order/create', 'POST', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callSf('/order/query', 'POST', ['orderNo' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->callSf('/track/query', 'POST', ['trackingNo' => $trackingNumber, 'language' => $language]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callSf('/order/cancel', 'POST', ['orderNo' => $orderId, 'reason' => $reason]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callSf('/rate/query', 'POST', [
            'mode'        => $data['mode'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => $data['weight'],
        ]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callSf('/label/print', 'POST', ['orderNo' => $orderId]);
    }

    public function declareCustoms(array $data): array
    {
        $this->validateCustomsData($data);
        return $this->callSf('/customs/declare', 'POST', $data);
    }
}
