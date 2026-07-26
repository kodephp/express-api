<?php

namespace Kode\ExpressApi\SeventeenTrack;

use Kode\ExpressApi\Common\AbstractAggregatorClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 17TRACK 聚合查询客户端（国际运单识别 + 轨迹查询）
 *
 * 17TRACK 是全球覆盖最广的运单识别源之一，可作为 CourierRecognizer 的
 * 权威回退，使任意国际 / 国内单号都能确定性识别归属承运商。
 *
 * 鉴权：请求头 `17token: <token>`，由 prepareRequestHeaders 覆写默认 Bearer 头。
 */
class Client extends AbstractAggregatorClient
{
    /**
     * 运单识别接口路径（按单号探测可能的承运商）
     *
     * @var string
     */
    private const DETECT_URI = '/trackings/v2.1/detect';

    /**
     * 轨迹查询接口路径
     *
     * @var string
     */
    private const TRACK_URI = '/trackings/v2.1/gettrack';

    /**
     * @return string
     */
    protected function getProvider(): string
    {
        return 'seventeentrack';
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
     * 17TRACK 直接按单号查询，无需预先识别承运商。
     *
     * @param string      $trackingNumber 运单号
     * @param string      $language       语言（透传）
     * @param string|null $courier        可选：承运商代码（17TRACK 忽略，按单号直查）
     * @return array
     * @throws ExpressApiException
     */
    public function queryTracking(string $trackingNumber, string $language = 'zh-CN', ?string $courier = null): array
    {
        if (empty($trackingNumber)) {
            throw new ExpressApiException('运单号不能为空');
        }

        $data = ['number' => $trackingNumber];

        return $this->request('POST', self::TRACK_URI, $data);
    }

    /**
     * 运单号自动识别（调用 17TRACK 探测接口）
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

        $data = ['number' => $trackingNumber];

        $response = $this->request('POST', self::DETECT_URI, $data);

        // 17TRACK 探测响应：{"data":{"matched":[{"code":"ups","name":"...","confidence":...}]}}
        $matched = $response['data']['matched'] ?? [];
        $code = (string) ($matched[0]['code'] ?? '');

        return ['courier' => $code, 'raw' => $response];
    }

    /**
     * 覆写请求头：以 17token 头替换默认 Bearer 鉴权头
     *
     * @param array $headers
     * @return array
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        unset($headers['Authorization']);
        $headers['17token'] = $this->auth->getAccessToken();

        return $headers;
    }
}
