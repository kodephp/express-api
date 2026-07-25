<?php

namespace Kode\ExpressApi\Debang;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\DomesticFreight\AbstractDomesticFreightClient;
use Kode\ExpressApi\DomesticFreight\CommonDomesticFreightOperations;

/**
 * 德邦物流（零担 / 整车 / 快运）API 客户端
 *
 * 真实接入：德邦开放平台，app_key + 时间戳 + MD5 签名；业务参数以 JSON 放请求体。
 * 端点（以开放平台文档为准）：
 *  - 下单：POST /api/express/order/add
 *  - 查询：POST /api/express/order/get
 *  - 轨迹：POST /api/trace/queryTrace
 *  - 取消：POST /api/express/order/cancel
 *  - 面单：POST /api/express/order/print
 *  - 报价：POST /api/price/query
 *  - 网点：POST /api/network/query
 */
class Client extends AbstractDomesticFreightClient
{
    use CommonDomesticFreightOperations;

    protected function getProvider(): string
    {
        return 'debang';
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
     * MD5 签名：app_key + 业务体(JSON 压缩) + timestamp + app_secret 拼接后取 32 位小写 MD5
     *
     * @param string $bodyJson
     * @return array 拼在 URL 后的公共鉴权参数
     */
    private function sign(string $bodyJson): array
    {
        $timestamp = (string) time();
        $raw  = $this->config->getAppKey() . $bodyJson . $timestamp . $this->config->getAppSecret();
        $sign = md5($raw);

        return [
            'app_key'   => $this->config->getAppKey(),
            'timestamp' => $timestamp,
            'sign'      => $sign,
            'format'    => 'json',
        ];
    }

    /**
     * 统一调用德邦开放接口
     *
     * @param string $path    接口路径（以 / 开头，拼在 BaseUrl 之后）
     * @param array  $bizData 业务数据
     * @return array
     * @throws ExpressApiException
     */
    private function callDebang(string $path, array $bizData): array
    {
        $bodyJson = json_encode($bizData, JSON_UNESCAPED_UNICODE);
        $params   = $this->sign($bodyJson);
        $url      = $this->config->getBaseUrl() . $path . '?' . http_build_query($params);

        $response = $this->transmit('POST', $url, $bizData, ['Content-Type' => 'application/json']);

        if (($response['success'] ?? false) !== true && (int) ($response['code'] ?? -1) !== 0) {
            throw new ExpressApiException('德邦接口调用失败: ' . ($response['message'] ?? '未知错误'), 0, $response);
        }

        return $response['data'] ?? $response;
    }

    /**
     * 下单 / 发货（按 service_type 区分零担 / 整车 / 快运）
     */
    public function sendShipment(array $data): array
    {
        $this->validateFreightShipment($data);

        $serviceMap = [
            self::SERVICE_LTL     => 'LTL',
            self::SERVICE_FTL      => 'FTL',
            self::SERVICE_EXPRESS  => 'EXPRESS',
        ];

        $payload = [
            'orderNo'     => $data['order_no'],
            'serviceType' => $serviceMap[$data['service_type']] ?? 'LTL',
            'sender'      => $data['sender'],
            'receiver'    => $data['receiver'],
            'goods'       => $data['goods'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
        ];

        return $this->callDebang('/api/express/order/add', $payload);
    }

    public function queryOrder(string $orderId): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callDebang('/api/express/order/get', ['orderNo' => $orderId]);
    }

    public function queryTracking(string $trackingNumber, string $language = 'zh-CN'): array
    {
        $this->requireId($trackingNumber, '运单号');
        return $this->callDebang('/api/trace/queryTrace', ['waybillNo' => $trackingNumber]);
    }

    public function cancelOrder(string $orderId, string $reason = ''): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callDebang('/api/express/order/cancel', ['orderNo' => $orderId, 'reason' => $reason]);
    }

    public function printLabel(string $orderId, array $data = []): array
    {
        $this->requireId($orderId, '订单号');
        return $this->callDebang('/api/express/order/print', ['orderNo' => $orderId]);
    }

    public function getQuotation(array $data): array
    {
        $this->validateQuotationData($data);
        return $this->callDebang('/api/price/query', [
            'serviceType' => $data['service_type'],
            'origin'      => $data['origin'],
            'destination' => $data['destination'],
            'weight'      => $data['weight'],
        ]);
    }

    /**
     * 网点查询（override trait 默认未实现）
     */
    public function queryNetwork(array $data = []): array
    {
        foreach (['city', 'keyword'] as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("网点查询缺少必填字段: {$field}");
            }
        }
        return $this->callDebang('/api/network/query', $data);
    }
}
