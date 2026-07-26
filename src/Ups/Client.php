<?php

namespace Kode\ExpressApi\Ups;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * UPS（联合包裹）API 客户端
 *
 * 真实接入：标准 REST，OAuth2 Bearer 鉴权，另需 header：transId（UUID）/ transactionSrc。
 * 端点：
 *  - 轨迹：GET /api/track/v1/details/{trackingNumber}
 *  - 下单：POST /api/ship/v1/shipments（海关申报随 ShipmentRequest.Shipment 提交）
 *  - 报价：POST /api/rating/v1/rates
 *  - 取消：DELETE /api/ship/v1/shipments/{id}
 *  - 面单：GET /api/ship/v1/shipments/{id}/labels
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'ups';
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
     * 注入 UPS 专属请求头（Bearer 鉴权 + transId / transactionSrc）
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        $headers['Authorization']    = 'Bearer ' . $this->auth->getAccessToken();
        $headers['transId']          = $this->uuid4();
        $headers['transactionSrc']   = 'kode-express-api';
        $headers['Accept']           = 'application/json';
        return $headers;
    }

    /**
     * 生成 RFC 4122 v4 UUID（UPS 要求 transId 为 UUID）
     */
    private function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 将收发货人扁平结构映射为 UPS 地址结构
     */
    private function mapParty(array $party): array
    {
        return [
            'Name'        => $party['name'] ?? '',
            'PhoneNumber' => $party['phone'] ?? '',
            'Address'     => [
                'AddressLine'       => [$party['address'] ?? ''],
                'City'              => $party['city'] ?? '',
                'PostalCode'        => $party['postcode'] ?? '',
                'CountryCode'       => $party['country'] ?? '',
            ],
        ];
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/api/track/v1/details/' . $trackingNumber,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data, false);

        $payload = [
            'ShipmentRequest' => [
                'Request' => ['RequestOption' => 'validate'],
                'Shipment' => [
                    'Shipper'    => $this->mapParty($data['sender'] ?? []),
                    'ShipTo'     => $this->mapParty($data['recipient'] ?? []),
                    'Service'    => ['Code' => $data['service_code'] ?? '65'], // 65 = 国际快递
                    'Package'    => [
                        'PackagingType' => ['Code' => $data['packaging_type'] ?? '02'],
                        'Weight'        => ['UnitOfMeasurement' => ['Code' => 'KGS'], 'Weight' => (string) ((float) ($data['weight'] ?? 1))],
                    ],
                    'Item' => [
                        'Description'      => $data['product_name'] ?? '',
                        'OriginCountryCode' => $data['origin_country'] ?? '',
                        'CommodityCode'    => $data['hs_code'] ?? '',
                        'UnitPrice'        => ['CurrencyCode' => $data['currency'] ?? 'USD', 'MonetaryValue' => (string) ((float) ($data['declared_value'] ?? 0))],
                    ],
                ],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/api/ship/v1/shipments',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        $payload = [
            'RateRequest' => [
                'Request' => ['RequestOption' => 'Rate'],
                'Shipment' => [
                    'Shipper'    => ['Address' => ['PostalCode' => $data['origin'] ?? '', 'CountryCode' => $data['origin_country'] ?? '']],
                    'ShipTo'     => ['Address' => ['PostalCode' => $data['destination'] ?? '', 'CountryCode' => $data['destination_country'] ?? '']],
                    'Package'    => [
                        'PackagingType' => ['Code' => '02'],
                        'Weight'        => ['UnitOfMeasurement' => ['Code' => 'KGS'], 'Weight' => (string) ((float) $data['weight'])],
                    ],
                ],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/api/rating/v1/rates',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/api/ship/v1/shipments/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'DELETE',
            $this->config->getBaseUrl() . '/api/ship/v1/shipments/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/api/ship/v1/shipments/' . $orderId . '/labels',
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
            'UPS 海关申报随 createShipment 一并提交（ShipmentRequest.Shipment.Item），无需单独调用'
        );
    }
}
