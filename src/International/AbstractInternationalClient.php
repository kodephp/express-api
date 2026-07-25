<?php

namespace Kode\ExpressApi\International;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * 国际物流客户端抽象基类
 *
 * 在统一快递客户端基类（HTTP 管道、配置 / 认证持有）之上，进一步收敛
 * 国际物流的通用校验与传输 helper，并约定一套统一的业务能力方法名
 * （sendShipment / queryOrder / queryTracking / declareCustoms / getQuotation ...），
 * 由各具体服务商子类（4PX、DHL、燕文、顺丰国际、云途、EMS 国际等）继承，
 * 以真实端点 + 差异化鉴权实现，从而做到「统一接入、特性继承处理」。
 *
 * 设计要点：
 *  - 国际物流各家鉴权差异极大（OAuth、MD5 签名、HMAC 签名、HTTP Basic），
 *    基类不再强制 Bearer 注入，而是提供 transmit() 直接复用 HttpClient 传输层
 *    （默认强制 SSL、超时、错误归一化），认证头与签名由各子类在 $headers 中自行构造。
 *  - 统一的业务能力方法名让 getApiMenu() 能自动发现各家能力，调用方 API 保持一致。
 *  - 接口要求的批量 / 拦截 / 改件等方法由 CommonInternationalOperations trait 提供
 *    默认实现（明确抛「暂未实现」），子类仅需 override 真实支持的几个方法，避免大量重复。
 */
abstract class AbstractInternationalClient extends AbstractCourierClient
{
    /**
     * 运输方式：海运
     */
    public const MODE_SEA = 'sea';

    /**
     * 运输方式：空运
     */
    public const MODE_AIR = 'air';

    /**
     * 统一传输 helper：直接复用 HttpClient（默认强制 SSL、超时、错误归一化）。
     *
     * 认证头 / 签名由各子类在其调用处自行构造后传入 $headers，因此不经由
     * 基类 AbstractCourierClient::request() 的 Bearer 注入流程。
     *
     * @param string $method  HTTP 方法
     * @param string $url     完整请求 URL
     * @param array  $payload 请求体（数组，自动 JSON 编码）
     * @param array  $headers 请求头（含鉴权 / 签名）
     * @return array
     * @throws ExpressApiException
     */
    protected function transmit(string $method, string $url, array $payload, array $headers): array
    {
        try {
            return HttpClient::request($method, $url, $payload, $headers, $this->config->getTimeout());
        } catch (\Exception $e) {
            if ($e instanceof ExpressApiException) {
                throw $e;
            }
            throw new ExpressApiException('国际物流请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 校验业务主键非空（订单号 / 运单号）
     *
     * 各子类在 queryOrder / queryTracking / cancelOrder / printLabel 中复用，
     * 缺省即视为非法调用，提前抛出 ExpressApiException（不触网络）。
     *
     * @param string $id
     * @param string $label
     * @throws ExpressApiException
     */
    protected function requireId(string $id, string $label): void
    {
        if (trim($id) === '') {
            throw new ExpressApiException("{$label}不能为空");
        }
    }

    /**
     * 国际物流下单通用校验（在统一必填项上叠加运输方式与海关申报要素）
     *
     * @param array $data
     * @param bool  $requireMode 是否强制要求 mode（部分服务商以 service 区分运输方式）
     * @throws ExpressApiException
     */
    protected function validateInternationalShipment(array $data, bool $requireMode = true): void
    {
        if ($requireMode && (!isset($data['mode']) || !in_array($data['mode'], [self::MODE_SEA, self::MODE_AIR], true))) {
            throw new ExpressApiException('国际物流下单必须指定运输方式 mode（sea 海运 / air 空运）');
        }

        foreach (['order_no', 'sender', 'recipient', 'items', 'destination_country'] as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("国际物流下单缺少必填字段: {$field}");
            }
        }

        // 海关申报要素（国际物流清关必备）
        foreach (['hs_code', 'product_name', 'declared_value', 'currency', 'origin_country'] as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("国际物流下单缺少海关申报字段: {$field}");
            }
        }

        foreach (['sender', 'recipient'] as $contact) {
            foreach (['name', 'phone', 'address'] as $field) {
                if (!isset($data[$contact][$field])) {
                    throw new ExpressApiException("{$contact}缺少必填字段: {$field}");
                }
            }
        }

        if (!is_array($data['items']) || empty($data['items'])) {
            throw new ExpressApiException('商品信息不能为空');
        }
    }

    /**
     * 海关申报要素校验
     *
     * @param array $data
     * @throws ExpressApiException
     */
    protected function validateCustomsData(array $data): void
    {
        foreach (['hs_code', 'product_name', 'declared_value', 'currency', 'origin_country'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ExpressApiException("海关申报缺少必填字段: {$field}");
            }
        }
    }

    /**
     * 运费报价要素校验
     *
     * @param array $data
     * @throws ExpressApiException
     */
    protected function validateQuotationData(array $data): void
    {
        foreach (['mode', 'origin', 'destination', 'weight'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ExpressApiException("运费报价缺少必填字段: {$field}");
            }
        }
    }

    /**
     * 海运下单（便捷入口，自动置 mode=sea）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function createSeaFreight(array $data): array
    {
        $data['mode'] = self::MODE_SEA;
        return $this->sendShipment($data);
    }

    /**
     * 空运下单（便捷入口，自动置 mode=air）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function createAirFreight(array $data): array
    {
        $data['mode'] = self::MODE_AIR;
        return $this->sendShipment($data);
    }
}
