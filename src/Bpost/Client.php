<?php

namespace Kode\ExpressApi\Bpost;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * bpost（比利时邮政）API 客户端
 *
 * 真实接入：标准 REST，API key 鉴权（请求头 X-ApiKey）。
 * 端点（以 bpost API 文档为准，此处为骨架）：
 *  - 轨迹：GET /tracking/v1/parcels/{parcelId}
 *  - 下单：POST /xshipper/v1/parcels（海关申报随 parcels.customs 提交）
 *  - 报价：POST /xshipper/v1/price
 *  - 订单：GET /xshipper/v1/parcels/{id}
 *  - 取消：DELETE /xshipper/v1/parcels/{id}
 *  - 面单：GET /xshipper/v1/parcels/{id}/label
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'bpost';
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
     * 注入 bpost 专属请求头（API key 鉴权）
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        unset($headers['Authorization']);
        $headers['X-ApiKey'] = $this->config->getAppKey();
        $headers['Accept']   = 'application/json';
        return $headers;
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/tracking/v1/parcels/' . $trackingNumber,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data, false);

        $payload = [
            'order_no'            => $data['order_no'],
            'mode'                => $data['mode'] ?? 'air',
            'sender'              => $data['sender'],
            'recipient'           => $data['recipient'],
            'destination_country' => $data['destination_country'],
            'weight'              => (float) ($data['weight'] ?? 1),
            'items'               => $data['items'],
            'customs'             => [
                'hs_code'         => $data['hs_code'],
                'product_name'    => $data['product_name'],
                'declared_value'  => $data['declared_value'],
                'currency'        => $data['currency'],
                'origin_country'  => $data['origin_country'],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/xshipper/v1/parcels',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        $payload = [
            'mode'        => $data['mode'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => (float) $data['weight'],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/xshipper/v1/price',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/xshipper/v1/parcels/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'DELETE',
            $this->config->getBaseUrl() . '/xshipper/v1/parcels/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/xshipper/v1/parcels/' . $orderId . '/label',
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
            'bpost 海关申报随 createShipment 一并提交（parcels.customs），无需单独调用'
        );
    }
}
