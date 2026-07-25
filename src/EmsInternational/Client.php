<?php

namespace Kode\ExpressApi\EmsInternational;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * 中国邮政 EMS 国际版 API 客户端
 *
 * 真实接入：中国邮政 API 开发者平台 api.ems.com.cn。
 * 鉴权使用 AppID（即 app_key）+ AppSecret，签名规则 MD5(AppID + RequestData + Timestamp + AppSecret)，Sign 大写。
 * 国际件需在业务数据中标识 CrossBorder 并携带 CustomsInfo（海关申报）。
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'ems_international';
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
     * 统一调用 EMS 国际接口（MD5 签名，业务数据包裹于 RequestData）
     */
    private function callEms(string $path, array $bizData): array
    {
        $requestData = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $timestamp   = (string) (int) (microtime(true) * 1000);
        $appId       = $this->config->getAppKey();
        $appSecret   = $this->config->getAppSecret();
        $sign        = strtoupper(md5($appId . $requestData . $timestamp . $appSecret));

        $url = $this->config->getBaseUrl() . $path;
        $payload = [
            'AppID'       => $appId,
            'Timestamp'   => $timestamp,
            'Sign'        => $sign,
            'RequestData' => $bizData,
        ];

        $response = $this->transmit('POST', $url, $payload, ['Content-Type' => 'application/json']);

        if (($response['success'] ?? true) === false || (isset($response['code']) && (int) $response['code'] !== 0)) {
            throw new ExpressApiException('EMS国际接口调用失败: ' . ($response['message'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data);
        $payload = [
            'OrderCode'   => $data['order_no'],
            'ShipperCode' => 'EMS',
            'CrossBorder' => true,
            'Sender'      => $data['sender'],
            'Receiver'    => $data['recipient'],
            'Goods'       => $data['items'],
            'CustomsInfo' => [
                'HsCode'        => $data['hs_code'],
                'ProductName'   => $data['product_name'],
                'DeclaredValue' => $data['declared_value'],
                'Currency'      => $data['currency'],
                'OriginCountry' => $data['origin_country'],
            ],
        ];
        return $this->callEms('/order/create', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callEms('/order/query', ['OrderCode' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->callEms('/track/query', ['LogisticCode' => $trackingNumber]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callEms('/order/cancel', ['OrderCode' => $orderId, 'reason' => $reason]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callEms('/rate/query', [
            'Mode'        => $data['mode'],
            'Origin'      => $data['origin'],
            'Destination' => $data['destination'],
            'Weight'      => $data['weight'],
        ]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callEms('/label/print', ['OrderCode' => $orderId]);
    }

    public function declareCustoms(array $data): array
    {
        $this->validateCustomsData($data);
        return $this->callEms('/customs/declare', $data);
    }
}
