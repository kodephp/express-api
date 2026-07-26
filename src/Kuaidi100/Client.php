<?php

namespace Kode\ExpressApi\Kuaidi100;

use Kode\ExpressApi\Common\AbstractAggregatorClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 快递100 聚合查询客户端（运单轨迹 + 承运商自动识别）
 */
class Client extends AbstractAggregatorClient
{
    /**
     * 轨迹查询接口路径
     *
     * @var string
     */
    private const TRACK_URI = '/Ecommerce/QueryTrack';

    /**
     * 自动识别接口路径
     *
     * @var string
     */
    private const RECOGNIZE_URI = '/autonumber/recognise';

    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'kuaidi100';
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
     * 若未显式传入 $courier，则先调用自动识别接口确定承运商。
     *
     * @param string      $trackingNumber 运单号
     * @param string      $language       语言（透传）
     * @param string|null $courier       可选：承运商代码
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

        // 快递100 实时查询接口要求的签名参数（key + 单号 + com 做 MD5）
        $key = $this->config->getAppKey();
        $customer = $this->config->getAppSecret();
        $param = json_encode([
            'num' => $trackingNumber,
            'com' => $com,
            'from' => '',
            'to'   => '',
        ], JSON_UNESCAPED_UNICODE);
        $sign = md5($param . $key . $customer);

        $data = [
            'customer' => $customer,
            'param'    => $param,
            'sign'     => $sign,
        ];

        return $this->request('POST', self::TRACK_URI, $data);
    }

    /**
     * 运单号自动识别（调用快递100 智能判定接口）
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

        $key = $this->config->getAppKey();
        $customer = $this->config->getAppSecret();
        $param = json_encode(['num' => $trackingNumber], JSON_UNESCAPED_UNICODE);
        $sign = md5($param . $key . $customer);

        $data = [
            'customer' => $customer,
            'param'    => $param,
            'sign'     => $sign,
        ];

        $response = $this->request('POST', self::RECOGNIZE_URI, $data);
        $com = (string) ($response['data']['comCode'] ?? ($response['comCode'] ?? ''));

        return ['courier' => $com, 'raw' => $response];
    }
}
