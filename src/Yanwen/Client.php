<?php

namespace Kode\ExpressApi\Yanwen;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * 燕文物流（YANWEN）API 客户端
 *
 * 真实接入：燕文开放平台，公共参数（user_id/format/method/timestamp/version/sign）拼在 URL，
 * 业务数据放在请求体 {"data": "<json 字符串>"}。
 * 签名 = MD5(apitoken + user_id + data + format + method + timestamp + version + apitoken)。
 * 轨迹查询走独立域名 api.track.yw56.com.cn（Header Authorization = 商户号）。
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'yanwen';
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
     * 统一调用燕文开放接口（下单 / 查询类）
     */
    private function callYanwen(string $methodName, array $bizData): array
    {
        $bodyJson = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $userId   = $this->config->getUserId();
        $apiToken = $this->config->getApiToken();
        $timestamp = (string) (int) (microtime(true) * 1000);
        $version  = 'V1.0';

        $signSrc = $apiToken . $userId . $bodyJson . 'json' . $methodName . $timestamp . $version . $apiToken;
        $sign = md5($signSrc);

        $query = http_build_query([
            'user_id'   => $userId,
            'format'    => 'json',
            'method'    => $methodName,
            'timestamp' => $timestamp,
            'version'   => $version,
            'sign'      => $sign,
        ]);

        $url = $this->config->getBaseUrl() . '?' . $query;
        $response = $this->transmit('POST', $url, ['data' => $bodyJson], ['Content-Type' => 'application/json']);

        if (($response['success'] ?? false) !== true) {
            throw new ExpressApiException('燕文接口调用失败: ' . ($response['message'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data);

        $payload = [
            'orderNumber'    => $data['order_no'],
            'transportMode'  => $data['mode'] === 'air' ? 'AIR' : 'SEA',
            'destinationCountry' => $data['destination_country'],
            'sender'         => $data['sender'],
            'recipient'      => $data['recipient'],
            'items'          => $data['items'],
            'importCustomsInfo' => [
                'hsCode'        => $data['hs_code'],
                'productName'   => $data['product_name'],
                'declaredValue' => $data['declared_value'],
                'currency'      => $data['currency'],
                'originCountry' => $data['origin_country'],
            ],
        ];

        return $this->callYanwen('express.order.create', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callYanwen('express.order.get', ['orderNumber' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        $url = $this->config->getTrackingBaseUrl() . '?nums=' . $trackingNumber;
        $headers = ['Authorization' => $this->config->getUserId()];
        return $this->transmit('GET', $url, [], $headers);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callYanwen('express.order.cancel', ['orderNumber' => $orderId]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callYanwen('express.price.query', [
            'mode'        => $data['mode'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => $data['weight'],
        ]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callYanwen('express.label.print', ['orderNumber' => $orderId]);
    }

    public function declareCustoms(array $data): array
    {
        $this->validateCustomsData($data);
        // 燕文海关申报信息（importCustomsInfo）随 createShipment 提交；此处校验要素后调用申报接口
        return $this->callYanwen('express.customs.declare', $data);
    }
}
