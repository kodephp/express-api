<?php

namespace Kode\ExpressApi\DomesticFreight;

use Kode\ExpressApi\Common\AbstractCourierClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\Common\HttpClient;

/**
 * 国内货运（零担 LTL / 整车 FTL / 快运）客户端抽象基类
 *
 * 在统一快递客户端基类（HTTP 管道、配置 / 认证持有）之上，进一步收敛
 * 国内货运的通用校验与传输 helper，并约定一套统一的业务能力方法名
 * （sendShipment / queryOrder / queryTracking / getQuotation / queryNetwork ...），
 * 由各具体服务商（德邦、安能、天地华宇等）继承，以真实端点 + 差异化鉴权实现，
 * 从而做到「统一接入、特性继承处理」。
 *
 * 设计要点：
 *  - 国内货运各家鉴权差异较大（MD5 签名 / HMAC 签名 / OAuth），
 *    基类不再强制 Bearer 注入，而是提供 transmit() 直接复用 HttpClient 传输层
 *    （默认强制 SSL、超时、错误归一化），签名 / 鉴权头由各子类在 $headers 中自行构造。
 *  - 统一的业务能力方法名让 getApiMenu() 能自动发现各家能力，调用方 API 保持一致。
 *  - 接口要求的批量 / 拦截 / 改件等方法由 CommonDomesticFreightOperations trait 提供
 *    默认实现（明确抛「暂未实现」），子类仅需 override 真实支持的几个方法，避免大量重复。
 */
abstract class AbstractDomesticFreightClient extends AbstractCourierClient
{
    /**
     * 服务类型：零担（Less Than Truckload）
     */
    public const SERVICE_LTL = 'ltl';

    /**
     * 服务类型：整车（Full Truckload）
     */
    public const SERVICE_FTL = 'ftl';

    /**
     * 服务类型：快运（含快递型货运）
     */
    public const SERVICE_EXPRESS = 'express';

    /**
     * 统一传输 helper：直接复用 HttpClient（默认强制 SSL、超时、错误归一化）。
     *
     * 签名 / 鉴权头由各子类在其调用处自行构造后传入 $headers，因此不经由
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
            throw new ExpressApiException('国内货运请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 校验业务主键非空（运单号 / 订单号）
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
     * 国内货运下单通用校验（发货人 / 收货人 / 货物 / 起止地 / 服务类型）
     *
     * @param array $data
     * @param bool  $requireService 是否强制要求 service_type
     * @throws ExpressApiException
     */
    protected function validateFreightShipment(array $data, bool $requireService = true): void
    {
        if ($requireService
            && (!isset($data['service_type'])
                || !in_array($data['service_type'], [self::SERVICE_LTL, self::SERVICE_FTL, self::SERVICE_EXPRESS], true))) {
            throw new ExpressApiException(
                '国内货运下单必须指定服务类型 service_type（ltl 零担 / ftl 整车 / express 快运）'
            );
        }

        foreach (['order_no', 'sender', 'receiver', 'goods', 'origin', 'destination'] as $field) {
            if (!isset($data[$field])) {
                throw new ExpressApiException("国内货运下单缺少必填字段: {$field}");
            }
        }

        foreach (['sender', 'receiver'] as $contact) {
            foreach (['name', 'phone', 'address'] as $field) {
                if (!isset($data[$contact][$field])) {
                    throw new ExpressApiException("{$contact}缺少必填字段: {$field}");
                }
            }
        }

        if (!is_array($data['goods']) || empty($data['goods'])) {
            throw new ExpressApiException('货物信息不能为空');
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
        foreach (['service_type', 'origin', 'destination', 'weight'] as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new ExpressApiException("运费报价缺少必填字段: {$field}");
            }
        }
    }

    /**
     * 零担下单（便捷入口，自动置 service_type=ltl）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function createLtl(array $data): array
    {
        $data['service_type'] = self::SERVICE_LTL;
        return $this->sendShipment($data);
    }

    /**
     * 整车下单（便捷入口，自动置 service_type=ftl）
     *
     * @param array $data
     * @return array
     * @throws ExpressApiException
     */
    public function createFtl(array $data): array
    {
        $data['service_type'] = self::SERVICE_FTL;
        return $this->sendShipment($data);
    }
}
