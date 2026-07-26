<?php

namespace Kode\ExpressApi\Common\Resolver;

/**
 * 聚合解析器：将多家聚合查询服务商的「运单号 → 外部承运商代码」结果，
 * 归一化映射为本 SDK 的内部承运商代码，作为 {@see \Kode\ExpressApi\Common\CourierRecognizer}
 * 的权威回退。
 *
 * 解决的问题：仅凭运单号无法用静态规则命中时（例如国际单、平台单），
 * 委托 17TRACK / 快递100 / 快递鸟等已接入的聚合源按真实接口解析归属，
 * 再把它们各自的外部承运商代码（如 17TRACK 的 "ups"、快递100 的 "shunfeng"）
 * 经别名表映射为本 SDK 的内部代码（如 "ups"（待接入） / "sf"）。
 *
 * 用法：
 * ```php
 * $resolver = new AggregateResolver();
 * $resolver->add($kuaidi100Client)->add($seventeenTrackClient);
 * // 作为可调用对象直接传给识别器
 * CourierRecognizer::setResolver($resolver);
 * // 或在单次识别时透传
 * ExpressApiClient::recognize($no, $resolver);
 * ```
 *
 * 单家聚合商识别失败（网络/鉴权）不会阻断整体，会继续尝试下一家；
 * 若所有聚合商都未能给出「本 SDK 已支持」的承运商，则返回 null。
 */
class AggregateResolver
{
    /**
     * 已注册的解析源
     *
     * @var ResolverSourceInterface[]
     */
    private array $sources = [];

    /**
     * 外部聚合服务商返回的承运商代码 → 本 SDK 内部承运商代码。
     *
     * 仅收录本 SDK 已支持的承运商；尚未接入（如圆通、FedEx、UPS）映射为 null，
     * 识别到时视为「已知但本 SDK 暂不支持」，解析器返回 null，等待后续补充渠道。
     *
     * @var array<string,string|null>
     */
    private static array $alias = [
        // —— 国内快递（快递100 / 快递鸟 常见 comCode）——
        'shunfeng'        => 'sf',
        'sf'              => 'sf',
        'yunda'           => 'yunda',
        'yd'              => 'yunda',
        'yt'              => 'yunda',
        'zhongtong'       => 'zto',
        'zto'             => 'zto',
        'zt'              => 'zto',
        'shentong'        => 'sto',
        'sto'             => 'sto',
        'st'              => 'sto',
        'ems'             => 'ems',
        'youzhengguonei'  => 'ems',
        'emssz'           => 'ems',
        'jd'              => 'jd',
        'jdzheng'         => 'jd',
        'jdexpress'       => 'jd',
        'debang'          => 'debang',
        'debangkuaidi'    => 'debang',
        'ane'             => 'ane',
        'hoau'            => 'hoau',
        // —— 国际 / 跨境 ——
        'dhl'             => 'dhl',
        'dhlint'          => 'dhl',
        'fourpx'          => 'fourpx',
        '4px'             => 'fourpx',
        'yunexpress'      => 'yunexpress',
        'uf'              => 'yunexpress',
        'yanwen'          => 'yanwen',
        'yw'              => 'yanwen',
        'emsint'          => 'ems_international',
        'emsinternational' => 'ems_international',
        'cnems'           => 'ems_international',
        'sfint'           => 'sf_international',
        'sfinternational' => 'sf_international',
        // —— 尚未接入（待补充渠道）——
        'fedex'           => null,
        'ups'             => null,
        'usps'            => null,
        'postnl'          => null,
        'royalmail'       => null,
        'bpost'           => null,
        'singpost'        => null,
        'yuantong'        => null,
        'htky'            => null,
    ];

    /**
     * 添加解析源（链式调用）
     *
     * @param ResolverSourceInterface $source
     * @return self
     */
    public function add(ResolverSourceInterface $source): self
    {
        $this->sources[] = $source;

        return $this;
    }

    /**
     * 已注册解析源数量
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->sources);
    }

    /**
     * 解析运单号归属的内部承运商代码
     *
     * 依次询问各解析源；第一个返回「本 SDK 已支持」承运商的结果即被采用。
     *
     * @param string $trackingNo 运单号
     * @return string|null 命中返回内部承运商代码（如 'sf'），否则返回 null
     */
    public function resolve(string $trackingNo): ?string
    {
        foreach ($this->sources as $source) {
            try {
                $result = $source->recognizeTracking($trackingNo);
            } catch (\Throwable $e) {
                // 单个聚合商失败不阻断，继续尝试下一家
                continue;
            }

            $external = strtolower((string) ($result['courier'] ?? ''));
            if ($external === '') {
                continue;
            }

            $internal = self::$alias[$external] ?? null;
            if ($internal !== null && $internal !== '') {
                return $internal;
            }
        }

        return null;
    }

    /**
     * 使本解析器可作为 callable 直接传给 CourierRecognizer::setResolver / detect
     *
     * @param string $trackingNo
     * @return string|null
     */
    public function __invoke(string $trackingNo): ?string
    {
        return $this->resolve($trackingNo);
    }

    /**
     * 获取外部代码别名表（调试 / 扩展用）
     *
     * @return array<string,string|null>
     */
    public static function aliasMap(): array
    {
        return self::$alias;
    }

    /**
     * 注册 / 覆盖某个外部代码的内部映射
     *
     * @param string      $external 外部承运商代码（自动转小写）
     * @param string|null $internal 本 SDK 内部承运商代码；null 表示已知但暂不支持
     * @return void
     */
    public static function registerAlias(string $external, ?string $internal): void
    {
        self::$alias[strtolower($external)] = $internal === null ? null : strtolower($internal);
    }
}
