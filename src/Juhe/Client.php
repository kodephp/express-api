<?php

namespace Kode\ExpressApi\Juhe;

use Kode\ExpressApi\Common\AbstractAggregatorClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 聚合数据 聚合查询客户端（运单轨迹 + 承运商自动识别）
 */
class Client extends AbstractAggregatorClient
{
    /**
     * 轨迹查询接口路径
     *
     * @var string
     */
    private const TRACK_URI = '/exp/index';

    /**
     * 自动识别接口路径
     *
     * @var string
     */
    private const RECOGNIZE_URI = '/express/autoComNum';

    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'juhe';
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

        $data = [
            'key'  => $this->config->getAppKey(),
            'com'  => $com,
            'no'   => $trackingNumber,
            'lang' => $language === 'en-US' ? 'en' : 'zh',
        ];

        return $this->request('POST', self::TRACK_URI, $data);
    }

    /**
     * 运单号自动识别（聚合数据智能判定接口）
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

        $data = [
            'key' => $this->config->getAppKey(),
            'num' => $trackingNumber,
        ];

        $response = $this->request('POST', self::RECOGNIZE_URI, $data);
        $com = (string) ($response['data']['comCode'] ?? ($response['comCode'] ?? ''));

        return ['courier' => $com, 'raw' => $response];
    }
}
