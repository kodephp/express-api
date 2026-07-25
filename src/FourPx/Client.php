<?php

namespace Kode\ExpressApi\FourPx;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\International\AbstractInternationalClient;
use Kode\ExpressApi\International\CommonInternationalOperations;

/**
 * 4PX 递四方 API 客户端
 *
 * 真实接入：递四方开放平台，公共参数（method/app_key/v/timestamp/format/sign/language/access_token）
 * 拼在 URL，业务数据以 JSON 放请求体；签名 = MD5(排序公共参数 + body + app_secret)。
 * 各 method 名称（如 ds.xms.order.create）以递四方 API 文档为准。
 */
class Client extends AbstractInternationalClient
{
    use CommonInternationalOperations;

    protected function getProvider(): string
    {
        return 'fourpx';
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
     * MD5 签名：除 access_token/language 外的公共参数按字典序拼接「参数名+值」，
     * 再拼接 body（JSON 压缩串）与 app_secret，取 32 位小写 MD5。
     */
    private function sign(array $params, string $bodyJson): string
    {
        $filtered = $params;
        unset($filtered['access_token'], $filtered['language']);
        ksort($filtered);

        $str = '';
        foreach ($filtered as $k => $v) {
            $str .= $k . $v;
        }
        $str .= $bodyJson . $this->config->getAppSecret();

        return md5($str);
    }

    /**
     * 统一调用 4PX 开放接口
     */
    private function call4px(string $methodName, array $bizData): array
    {
        $bodyJson = json_encode($bizData, JSON_UNESCAPED_UNICODE);

        $params = [
            'method'      => $methodName,
            'app_key'     => $this->config->getAppKey(),
            'v'           => '1.0',
            'timestamp'   => (string) (int) (microtime(true) * 1000),
            'format'      => 'json',
            'language'    => 'cn',
            'access_token' => $this->auth->getAccessToken(),
        ];
        $params['sign'] = $this->sign($params, $bodyJson);

        $url = $this->config->getBaseUrl() . '?' . http_build_query($params);
        $response = $this->transmit('POST', $url, $bizData, ['Content-Type' => 'application/json']);

        if (($response['result'] ?? 0) != 1) {
            throw new ExpressApiException('4PX接口调用失败: ' . ($response['msg'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    /**
     * 下单 / 发货（按 mode 区分海运 / 空运）
     */
    public function sendShipment(array $data): array
    {
        $this->validateInternationalShipment($data);

        $payload = [
            'order_no'            => $data['order_no'],
            'transport_mode'      => $data['mode'] === 'air' ? 'AIR' : 'SEA',
            'destination_country' => $data['destination_country'],
            'sender'              => $data['sender'],
            'recipient'           => $data['recipient'],
            'items'               => $data['items'],
            'customs'             => [
                'hs_code'         => $data['hs_code'],
                'product_name'    => $data['product_name'],
                'declared_value'  => $data['declared_value'],
                'currency'        => $data['currency'],
                'origin_country'  => $data['origin_country'],
            ],
        ];

        return $this->call4px('ds.xms.order.create', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->call4px('ds.xms.order.get', ['order_no' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'en-US'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->call4px('ds.xms.tracking.get', ['tracking_no' => $trackingNumber]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->call4px('ds.xms.order.cancel', ['order_no' => $orderId, 'reason' => $reason]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->call4px('ds.xms.rate.query', [
            'mode'        => $data['mode'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => $data['weight'],
        ]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->call4px('ds.xms.label.print', ['order_no' => $orderId]);
    }

    public function pickupNotice(array $data): array
    {
        foreach (['pickup_address', 'contact_person', 'contact_phone', 'cargo'] as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("取件数据缺少必填字段: {$field}");
            }
        }
        return $this->call4px('ds.xms.pickup.create', $data);
    }

    public function declareCustoms(array $data): array
    {
        $this->validateCustomsData($data);
        return $this->call4px('ds.xms.customs.declare', $data);
    }
}
