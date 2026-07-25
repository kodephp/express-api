<?php

namespace Kode\ExpressApi\Dhl;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * DHL Express（MyDHL API）API 客户端
 *
 * 真实接入：标准 REST，HTTP Basic 鉴权（Authorization = Basic base64(apiKey:apiSecret)），
 * 另需 header：Accept / Message-Reference / Message-Reference-Date / x-version。
 * 端点：POST /rates（报价）、POST /shipments（下单+面单）、GET /shipments/{id}/tracking（轨迹）。
 * 海关申报（exportDeclaration）随下单提交，无独立接口。
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'dhl';
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
     * 注入 DHL 专属请求头（覆盖基类默认 Bearer 为 Basic 鉴权）
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        $headers['Authorization']            = $this->auth->getAccessToken();
        $headers['Accept']                   = 'application/json';
        $headers['Message-Reference']        = $this->uuid4();
        $headers['Message-Reference-Date']   = gmdate('r');
        $headers['x-version']                = '3.2.2';
        return $headers;
    }

    /**
     * 生成 RFC 4122 v4 UUID（DHL 要求 Message-Reference ≥ 28 字符）
     */
    private function uuid4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 将收发货人扁平结构映射为 DHL 地址结构
     */
    private function mapParty(array $party): array
    {
        return [
            'name'          => $party['name'] ?? '',
            'postCode'      => $party['postcode'] ?? '',
            'city'          => $party['city'] ?? '',
            'countryCode'   => $party['country'] ?? '',
            'addressLine1'  => $party['address'] ?? '',
            'phone'         => $party['phone'] ?? '',
        ];
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        $payload = [
            'originPostalCode'      => $data['origin'] ?? '',
            'originCountryCode'     => $data['origin_country'] ?? '',
            'destinationPostalCode' => $data['destination'] ?? '',
            'destinationCountryCode' => $data['destination_country'] ?? '',
            'weight'                => (float) $data['weight'],
            'plannedShippingDate'   => $data['planned_shipping_date'] ?? date('Y-m-d'),
            'isCustomsDeclarable'   => true,
            'unitOfMeasurement'     => 'metric',
        ];
        return $this->request('POST', '/rates', $payload);
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data, false);

        $payload = [
            'plannedShippingDateAndTime' => $data['planned_shipping_date'] ?? date('c'),
            'productCode'                => $data['product_code'] ?? 'P',
            'accounts'                   => [
                ['typeCode' => 'shipper', 'number' => $this->config->getAccountNumber()],
            ],
            'customerDetails' => [
                'shipperDetails'  => $this->mapParty($data['sender']),
                'receiverDetails' => $this->mapParty($data['recipient']),
            ],
            'content' => [
                'packages'           => [['weight' => (float) ($data['weight'] ?? 1)]],
                'isCustomsDeclarable' => true,
                'declaredValue'      => $data['declared_value'] ?? 0,
                'description'        => $data['product_name'] ?? '',
            ],
        ];
        return $this->request('POST', '/shipments', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->request('GET', '/shipments/' . $orderId, []);
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->request('GET', '/shipments/' . $trackingNumber . '/tracking', []);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->request('DELETE', '/shipments/' . $orderId, []);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->request('GET', '/shipments/' . $orderId . '/images', []);
    }

    public function declareCustoms(array $data): array
    {
        if (is_array($data)) {
            $this->validateCustomsData($data);
        }
        throw new ExpressApiException(
            'DHL 海关申报随 createShipment 一并提交（content.exportDeclaration），无需单独调用'
        );
    }
}
