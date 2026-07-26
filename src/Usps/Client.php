<?php

namespace Kode\ExpressApi\Usps;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * USPS（美国邮政）API 客户端
 *
 * 真实接入：标准 REST，OAuth2 Bearer 鉴权（Authorization = Bearer <token>）。
 * 端点（以 USPS API 文档为准，此处为骨架）：
 *  - 轨迹：GET /track/v3/tracking/{trackingNumber}
 *  - 下单：POST /shipments/v3/shipments（海关申报随 shipments.customs 提交）
 *  - 报价：POST /prices/v3/prices
 *  - 订单：GET /shipments/v3/shipments/{id}
 *  - 取消：DELETE /shipments/v3/shipments/{id}
 *  - 面单：GET /shipments/v3/shipments/{id}/label
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'usps';
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
     * 注入 USPS 专属请求头（Bearer 鉴权）
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        $headers['Authorization'] = 'Bearer ' . $this->auth->getAccessToken();
        $headers['Accept']        = 'application/json';
        return $headers;
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/track/v3/tracking/' . $trackingNumber,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data, false);

        $payload = [
            'order_no'           => $data['order_no'],
            'mode'               => $data['mode'] ?? 'air',
            'sender'             => $data['sender'],
            'recipient'          => $data['recipient'],
            'destination_country' => $data['destination_country'],
            'weight'             => (float) ($data['weight'] ?? 1),
            'items'              => $data['items'],
            'customs'            => [
                'hs_code'         => $data['hs_code'],
                'product_name'    => $data['product_name'],
                'declared_value'  => $data['declared_value'],
                'currency'        => $data['currency'],
                'origin_country'  => $data['origin_country'],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/shipments/v3/shipments',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        $payload = [
            'mode'       => $data['mode'],
            'origin'     => $data['origin'],
            'destination' => $data['destination'],
            'weight'     => (float) $data['weight'],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/prices/v3/prices',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/shipments/v3/shipments/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'DELETE',
            $this->config->getBaseUrl() . '/shipments/v3/shipments/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/shipments/v3/shipments/' . $orderId . '/label',
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function declareCustoms(array $data): array
    {
        if (is_array($data)) {
            $this->validateCustomsData($data);
        }
        throw new ExpressApiException(
            'USPS 海关申报随 createShipment 一并提交（shipments.customs），无需单独调用'
        );
    }
}
