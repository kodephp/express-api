<?php

namespace Kode\ExpressApi\Fedex;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * FedEx（联邦快递）API 客户端
 *
 * 真实接入：标准 REST，OAuth2 Bearer 鉴权（Authorization = Bearer <token>），
 * 另需 header：X-locale / Accept。端点：
 *  - 轨迹：POST /track/v1/trackingnumbers
 *  - 下单：POST /ship/v1/shipments（海关申报随 requestedShipment.customsClearanceDetail 提交）
 *  - 报价：POST /rate/v1/rates/quotes
 *  - 订单：GET /ship/v1/shipments/{id}
 *  - 取消：DELETE /ship/v1/shipments/{id}
 *  - 面单：GET /ship/v1/shipments/{id}/labels
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'fedex';
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
     * 注入 FedEx 专属请求头（Bearer 鉴权 + locale）
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        $headers['Authorization'] = 'Bearer ' . $this->auth->getAccessToken();
        $headers['X-locale']      = 'en_US';
        $headers['Accept']        = 'application/json';
        return $headers;
    }

    /**
     * 将收发货人扁平结构映射为 FedEx 地址结构
     */
    private function mapParty(array $party): array
    {
        return [
            'contact' => [
                'personName'  => $party['name'] ?? '',
                'phoneNumber' => $party['phone'] ?? '',
            ],
            'address' => [
                'streetLines' => [$party['address'] ?? ''],
                'city'        => $party['city'] ?? '',
                'postalCode'  => $party['postcode'] ?? '',
                'countryCode' => $party['country'] ?? '',
            ],
        ];
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        $payload = [
            'includeDetailedScans' => true,
            'trackingInfo'         => [
                ['trackingNumberInfo' => ['trackingNumber' => $trackingNumber]],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/track/v1/trackingnumbers',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data, false);

        $payload = [
            'requestedShipment' => [
                'shipper'    => $this->mapParty($data['sender'] ?? []),
                'recipients' => [$this->mapParty($data['recipient'] ?? [])],
                'shipDatestamp' => date('Y-m-d'),
                'serviceType'   => $data['service_type'] ?? 'INTERNATIONAL_PRIORITY',
                'packagingType' => $data['packaging_type'] ?? 'YOUR_PACKAGING',
                'weight'        => [
                    'units' => 'KG',
                    'value' => (float) ($data['weight'] ?? 1),
                ],
                'customsClearanceDetail' => [
                    'commercialInvoice' => ['comments' => $data['product_name'] ?? ''],
                    'commodities'       => [
                        [
                            'description'         => $data['product_name'] ?? '',
                            'countryOfManufacture' => $data['origin_country'] ?? '',
                            'harmonizedCode'      => $data['hs_code'] ?? '',
                            'unitPrice'           => [
                                'currency' => $data['currency'] ?? 'USD',
                                'amount'   => (float) ($data['declared_value'] ?? 0),
                            ],
                        ],
                    ],
                ],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/ship/v1/shipments',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        $payload = [
            'accountNumber'    => ['value' => $this->config->getAccountNumber()],
            'requestedShipment' => [
                'shipper'    => ['address' => ['postalCode' => $data['origin'] ?? '', 'countryCode' => $data['origin_country'] ?? '']],
                'recipient'  => ['address' => ['postalCode' => $data['destination'] ?? '', 'countryCode' => $data['destination_country'] ?? '']],
                'totalPackageWeight' => ['units' => 'KG', 'value' => (float) $data['weight']],
            ],
        ];
        return $this->transmit(
            'POST',
            $this->config->getBaseUrl() . '/rate/v1/rates/quotes',
            $payload,
            $this->prepareRequestHeaders([])
        );
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/ship/v1/shipments/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'DELETE',
            $this->config->getBaseUrl() . '/ship/v1/shipments/' . $orderId,
            [],
            $this->prepareRequestHeaders([])
        );
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->transmit(
            'GET',
            $this->config->getBaseUrl() . '/ship/v1/shipments/' . $orderId . '/labels',
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
            'FedEx 海关申报随 createShipment 一并提交（requestedShipment.customsClearanceDetail），无需单独调用'
        );
    }
}
