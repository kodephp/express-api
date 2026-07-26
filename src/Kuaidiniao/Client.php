<?php

namespace Kode\ExpressApi\Kuaidiniao;

use Kode\ExpressApi\Common\AbstractAggregatorClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 快递鸟 聚合查询客户端（运单轨迹 + 承运商自动识别）
 */
class Client extends AbstractAggregatorClient
{
    /**
     * 统一接口路径（快递鸟按 RequestType 区分动作）
     *
     * @var string
     */
    private const API_URI = '/EOrderService';

    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'kuaidiniao';
    }

    /**
     * @return string
     */
    protected function getConfigClass(): string
    {
        return Config::class;
    }

    /**
     * @return string
     */
    protected function getAuthClass(): string
    {
        return Auth::class;
    }

    /**
     * 查询轨迹（聚合查询）
     *
     * @param string      $trackingNumber 运单号
     * @param string      $language        语言（透传）
     * @param string|null $courier        可选：承运商代码
     * @return array
     * @throws ExpressApiException
     */
    public function queryTracking(string $trackingNumber, string $language = 'zh-CN', ?string $courier = null): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }

        $com = $courier;
        if ($com === null || $com === '') {
            $recognized = $this->recognizeTracking($trackingNumber);
            $com = (string) ($recognized['courier'] ?? '');
            if ($com === '') {
                throw new ExpressApiException('无法自动识别承运商，请显式传入 courier 参数');
            }
        }

        // 快递鸟轨迹即时查询：RequestType=1002，DataSign = Base64(MD5(RequestData + ApiKey))
        $requestData = json_encode([
            'ShipperCode' => $com,
            'LogisticCode' => $trackingNumber,
        ], JSON_UNESCAPED_UNICODE);

        $apiKey = $this->config->getAppSecret();
        $ebusinessId = $this->config->getAppKey();
        $dataSign = base64_encode(md5($requestData . $apiKey));

        $data = [
            'RequestData'  => urlencode($requestData),
            'EBusinessID'  => $ebusinessId,
            'RequestType'  => '1002',
            'DataSign'     => urlencode($dataSign),
            'DataType'     => '2',
        ];

        return $this->request('POST', self::API_URI, $data);
    }

    /**
     * 运单号自动识别（快递鸟即时查询识别接口 RequestType=2002）
     *
     * @param string $trackingNumber 运单号
     * @return array ['courier' => string, 'raw' => array]
     * @throws ExpressApiException
     */
    public function recognizeTracking(string $trackingNumber): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }

        $requestData = json_encode(['LogisticCode' => $trackingNumber], JSON_UNESCAPED_UNICODE);
        $apiKey = $this->config->getAppSecret();
        $ebusinessId = $this->config->getAppKey();
        $dataSign = base64_encode(md5($requestData . $apiKey));

        $data = [
            'RequestData' => urlencode($requestData),
            'EBusinessID' => $ebusinessId,
            'RequestType' => '2002',
            'DataSign'    => urlencode($dataSign),
            'DataType'    => '2',
        ];

        $response = $this->request('POST', self::API_URI, $data);
        $shipper = (string) ($response['data']['Shippers'][0]['ShipperCode'] ?? ($response['ShipperCode'] ?? ''));

        return ['courier' => $shipper, 'raw' => $response];
    }
}
