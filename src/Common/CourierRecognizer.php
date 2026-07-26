<?php

namespace Kode\ExpressApi\Common;

/**
 * 承运商自动识别器
 *
 * 解决「不用自己去指定物流链的物流」这一核心诉求：仅凭一段运单号，
 * 即可自动推断其归属的快递 / 货运 / 国际物流服务商，进而联动物流链编排器
 * （LogisticsChain）自动拼装「揽收 → 干线 → 跨境 → 清关 → 末端」全链路。
 *
 * 识别策略分两级：
 *  1) 静态规则：内置各服务商运单号特征（前缀 / 长度 / 字符集）正则，离线可用、零成本；
 *  2) 动态解析器（可选）：当规则无法命中时，委托已接入的聚合查询服务商
 *     （如快递100 / 快递鸟 / 聚合数据）按真实接口解析归属，作为权威回退。
 *
 * 注意：运单号规则为「最佳努力（best-effort）」启发式，部分服务商号段存在重叠，
 * 强烈建议在生产环境配置聚合解析器以获得确定性结果。
 */
class CourierRecognizer
{
    /**
     * 内置运单号特征规则：承运商代码 => 正则数组（满足任一即命中）
     *
     * 顺序即优先级（更具体的服务商靠前），detect() 返回首个命中的承运商。
     *
     * @var array<string,string[]>
     */
    private static array $patterns = [
        // —— 国内快递 ——
        'ems'              => ['/^E[A-Z]\d{9}$/i', '/^[A-Z]{2}\d{9}[A-Z]{2}$/i'],
        'jd'               => ['/^JD\d/i', '/^JDX/i', '/^JDE/i'],
        'sf'               => ['/^SF\d{12,15}$/i'],
        'yunda'            => ['/^YD\d/i'],
        'zto'              => ['/^(73|75|ZT|ZTO)\d/i'],
        'sto'              => ['/^(77|STO|ST)\d/i'],
        'cainiao'          => ['/^[A-Za-z0-9]{20,}$/'],
        'jt'               => ['/^JT\d/i'],
        'yto'              => ['/^YT\d/i'],
        'best'             => ['/^BS\d/i', '/^BEST/i'],
        'usps'             => ['/^9[0-9]{15,}$/'],
        'postnl'           => ['/^3S/i'],
        'royalmail'        => ['/^[A-Z]{2}\d{11,}[A-Z]{2}$/i'],
        'bpost'            => ['/^(3V|JV)\d/i'],
        'singpost'         => ['/^(RR|SS|SG)\d/i'],
        // —— 国际物流 ——
        'fourpx'           => ['/^(4PX|LP)\d/i'],
        'yunexpress'       => ['/^(UF|CN|LK)\d/i'],
        'yanwen'           => ['/^Y\d{10,}$/i'],
        'ems_international' => ['/^[A-Z]{2}\d{9}[A-Z]{2}$/i'],
        'dhl'              => ['/^\d{10,11}$/', '/^JJD/i'],
        'sf_international' => ['/^SF/i'],
        'fedex'            => ['/^\d{12,14}$/', '/^\d{15}$/'],
        'ups'              => ['/^1Z[0-9A-Z]{16}$/i'],
        // —— 国内货运 ——
        'debang'           => ['/^(DPK|DEB|DE)\d/i'],
        'ane'              => ['/^(AN|ANE)\d/i'],
        'hoau'             => ['/^(HQ|HOAU)\d/i'],
    ];

    /**
     * 动态解析器（可选）：规则未命中时用于权威回退。
     * 签名为 callable(string $trackingNo): ?string，返回承运商代码或 null。
     *
     * @var callable|null
     */
    private static $resolver = null;

    /**
     * 识别单个运单号的归属承运商
     *
     * @param string        $trackingNo 运单号
     * @param callable|null $resolver   本次调用可选的解析器（覆盖全局设置），
     *                                  签名为 callable(string): ?string
     * @return string|null 命中返回承运商代码（如 'sf'），未命中返回 null
     */
    public static function detect(string $trackingNo, ?callable $resolver = null): ?string
    {
        $trackingNo = trim($trackingNo);
        if ($trackingNo === '') {
            return null;
        }

        // 第一级：静态规则
        foreach (self::$patterns as $courier => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $trackingNo) === 1) {
                    return $courier;
                }
            }
        }

        // 第二级：动态解析器（聚合查询回退，优先用本次调用传入的，否则用全局设置）
        $resolver = $resolver ?? self::$resolver;
        if ($resolver !== null) {
            $result = call_user_func($resolver, $trackingNo);
            if (is_string($result) && $result !== '') {
                return strtolower($result);
            }
        }

        return null;
    }

    /**
     * 批量识别
     *
     * @param string[] $trackingNos 运单号列表
     * @return array<string,string|null> 运单号 => 承运商代码（未命中为 null）
     */
    public static function detectBatch(array $trackingNos): array
    {
        $result = [];
        foreach ($trackingNos as $no) {
            $result[$no] = self::detect((string) $no);
        }
        return $result;
    }

    /**
     * 注册 / 覆盖某承运商的运单号特征规则
     *
     * @param string       $courier 承运商代码
     * @param string|string[] $regex 单个正则或正则数组
     * @return void
     */
    public static function registerPattern(string $courier, $regex): void
    {
        $courier = strtolower($courier);
        self::$patterns[$courier] = (array) $regex;
    }

    /**
     * 设置动态解析器（聚合查询回退）
     *
     * @param callable|null $resolver 签名为 callable(string): ?string
     * @return void
     */
    public static function setResolver(?callable $resolver): void
    {
        self::$resolver = $resolver;
    }

    /**
     * 获取当前全部内置规则（用于调试 / 文档生成）
     *
     * @return array<string,string[]>
     */
    public static function allPatterns(): array
    {
        return self::$patterns;
    }

    /**
     * 重置为内置默认规则，并清除动态解析器（主要用于测试隔离）
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$patterns = [
            'ems'              => ['/^E[A-Z]\d{9}$/i', '/^[A-Z]{2}\d{9}[A-Z]{2}$/i'],
            'jd'               => ['/^JD\d/i', '/^JDX/i', '/^JDE/i'],
            'sf'               => ['/^SF\d{12,15}$/i'],
            'yunda'            => ['/^YD\d/i'],
            'zto'              => ['/^(73|75|ZT|ZTO)\d/i'],
            'sto'              => ['/^(77|STO|ST)\d/i'],
            'cainiao'          => ['/^[A-Za-z0-9]{20,}$/'],
            'jt'               => ['/^JT\d/i'],
            'yto'              => ['/^YT\d/i'],
            'best'             => ['/^BS\d/i', '/^BEST/i'],
            'usps'             => ['/^9[0-9]{15,}$/'],
            'postnl'           => ['/^3S/i'],
            'royalmail'        => ['/^[A-Z]{2}\d{11,}[A-Z]{2}$/i'],
            'bpost'            => ['/^(3V|JV)\d/i'],
            'singpost'         => ['/^(RR|SS|SG)\d/i'],
            'fourpx'           => ['/^(4PX|LP)\d/i'],
            'yunexpress'       => ['/^(UF|CN|LK)\d/i'],
            'yanwen'           => ['/^Y\d{10,}$/i'],
            'ems_international' => ['/^[A-Z]{2}\d{9}[A-Z]{2}$/i'],
            'dhl'              => ['/^\d{10,11}$/', '/^JJD/i'],
            'sf_international' => ['/^SF/i'],
            'fedex'            => ['/^\d{12,14}$/', '/^\d{15}$/'],
            'ups'              => ['/^1Z[0-9A-Z]{16}$/i'],
            'debang'           => ['/^(DPK|DEB|DE)\d/i'],
            'ane'              => ['/^(AN|ANE)\d/i'],
            'hoau'             => ['/^(HQ|HOAU)\d/i'],
        ];
        self::$resolver = null;
    }
}
