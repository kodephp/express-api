<?php

namespace Kode\ExpressApi\LogisticsChain;

use Kode\ExpressApi\Common\CourierRecognizer;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\ExpressApiClient;

/**
 * 物流链自动编排器
 *
 * 解决核心诉求：「物流链应该是自动关联物流信息，不用自己去指定物流链的物流」。
 *
 * 两种自动关联入口：
 *  1) compose() —— 给定发货意图（起止国家 / 重量 / 运输方式），自动挑选每个环节
 *     （揽收 → 干线 → 跨境 → 清关 → 末端）的承运商并拼装整条链路，无需逐段指定；
 *  2) fromTracking() —— 给定一段运单号，自动识别归属承运商，并推断完整物流链路模板。
 *
 * 编排结果可经 track() 统一查询整条链路的轨迹，单段失败相互隔离。
 *
 * 各环节默认推荐承运商可被 $prefer 覆盖，也可仅传入已签约的 $configs，
 * 编排器会优先采用「已配置」的承运商，缺配环节自动降级 / 标记 unavailable。
 */
class LogisticsChain
{
    /** 运输方式：国内 */
    public const MODE_DOMESTIC = 'domestic';
    /** 运输方式：国际 */
    public const MODE_INTERNATIONAL = 'international';

    /** 物流链环节：揽收（国内快递） */
    public const LEG_PICKUP = 'pickup';
    /** 物流链环节：干线（国内货运） */
    public const LEG_LINEHAUL = 'linehaul';
    /** 物流链环节：跨境干线（空运 / 海运） */
    public const LEG_CROSSBORDER = 'crossborder';
    /** 物流链环节：清关 */
    public const LEG_CUSTOMS = 'customs';
    /** 物流链环节：末端派送 */
    public const LEG_LASTMILE = 'lastmile';

    /** 各环节默认推荐承运商（可被 $prefer 与「已配置」情况覆盖） */
    private static array $defaultChain = [
        self::LEG_PICKUP       => 'sf',
        self::LEG_LINEHAUL     => 'debang',
        self::LEG_CROSSBORDER  => 'dhl',
        self::LEG_CUSTOMS      => 'ems_international',
        self::LEG_LASTMILE     => 'jd',
    ];

    /** 空运 / 海运 跨境环节的候选优先级（取 $configs 中首个已配置者） */
    private static array $crossBorderByMode = [
        'air' => ['dhl', 'sf_international', 'fourpx', 'yunexpress', 'ems_international', 'yanwen', 'fedex', 'ups', 'usps', 'postnl', 'royalmail', 'bpost', 'singpost'],
        'sea' => ['fourpx', 'yunexpress', 'ems_international', 'dhl', 'sf_international', 'yanwen', 'fedex', 'ups', 'usps', 'postnl', 'royalmail', 'bpost', 'singpost'],
    ];

    /** 各环节可按「类目」回退的承运商清单 */
    private static array $categoryFallback = [
        self::LEG_PICKUP      => ['ems', 'sf', 'yunda', 'zto', 'sto', 'cainiao', 'jd', 'jt', 'yto', 'best'],
        self::LEG_LINEHAUL    => ['debang', 'ane', 'hoau'],
        self::LEG_CROSSBORDER => ['fourpx', 'sf_international', 'dhl', 'yunexpress', 'ems_international', 'yanwen', 'fedex', 'ups', 'usps', 'postnl', 'royalmail', 'bpost', 'singpost'],
        self::LEG_CUSTOMS     => ['ems_international', 'fourpx', 'yanwen', 'dhl', 'sf_international', 'fedex', 'ups', 'usps', 'postnl', 'royalmail', 'bpost', 'singpost'],
        self::LEG_LASTMILE    => ['ems', 'sf', 'yunda', 'zto', 'sto', 'cainiao', 'jd', 'jt', 'yto', 'best',
            'fourpx', 'sf_international', 'dhl', 'yunexpress', 'ems_international', 'yanwen', 'fedex', 'ups', 'usps', 'postnl', 'royalmail', 'bpost', 'singpost'],
    ];

    /** @var array 原始发货意图 */
    private array $request;
    /** @var array 已拼装的环节列表 */
    private array $legs = [];
    /** @var string|null 由运单号识别出的承运商 */
    private ?string $detectedCourier = null;
    /** @var array|null 由运单号推断的完整链路模板 */
    private ?array $suggestedChain = null;

    private function __construct(array $request)
    {
        $this->request = $request;
    }

    /**
     * 给定发货意图，自动组装物流链（无需逐段指定承运商）
     *
     * @param array $request 发货意图，支持键：
     *                     - origin: 起运国家 / 地区代码（如 'CN'）
     *                     - dest:   目的国家 / 地区代码（如 'US'）
     *                     - weight: 重量（kg，影响是否走干线货运）
     *                     - mode:   'air' | 'sea' | 'auto'（跨境运输方式，默认 auto→air）
     * @param array $configs 已签约承运商的配置，键为承运商代码
     * @param array $prefer  环节级承运商覆盖，如 ['crossborder' => 'fourpx']
     * @return static
     * @throws ExpressApiException
     */
    public static function compose(array $request, array $configs, array $prefer = []): self
    {
        $chain = new self($request);

        $origin = strtoupper((string) ($request['origin'] ?? 'CN'));
        $dest = strtoupper((string) ($request['dest'] ?? 'CN'));
        $mode = strtolower((string) ($request['mode'] ?? 'auto'));
        if ($mode === 'auto') {
            $mode = 'air';
        }
        $isInternational = $origin !== '' && $dest !== '' && $origin !== $dest;

        // 确定链路模板（国内 / 国际）
        $template = $isInternational
            ? [self::LEG_PICKUP, self::LEG_LINEHAUL, self::LEG_CROSSBORDER, self::LEG_CUSTOMS, self::LEG_LASTMILE]
            : [self::LEG_PICKUP, self::LEG_LINEHAUL];

        foreach ($template as $leg) {
            $courier = self::resolveCourier($leg, $mode, $configs, $prefer);
            $chain->legs[$leg] = [
                'leg'      => $leg,
                'courier'  => $courier,
                'name'     => $courier !== null ? (ExpressApiClient::getSupportedCouriers()[$courier] ?? $courier) : null,
                'client'   => $courier !== null ? ExpressApiClient::create($courier, $configs[$courier]) : null,
            ];
        }

        return $chain;
    }

    /**
     * 给定运单号，自动识别承运商并推断完整物流链路
     *
     * @param string $trackingNo 运单号
     * @param array  $configs    已签约承运商的配置
     * @return static
     * @throws ExpressApiException 运单号无法识别且未配置解析器时抛出
     */
    public static function fromTracking(string $trackingNo, array $configs): self
    {
        $chain = new self(['tracking_no' => $trackingNo]);

        $courier = CourierRecognizer::detect($trackingNo);
        if ($courier === null) {
            throw new ExpressApiException(
                "无法从运单号 [{$trackingNo}] 自动识别承运商；请配置聚合解析器或显式指定。"
            );
        }
        $chain->detectedCourier = $courier;

        // 单段已识别承运商
        $chain->legs[self::LEG_LASTMILE] = [
            'leg'      => self::LEG_LASTMILE,
            'courier'  => $courier,
            'name'     => ExpressApiClient::getSupportedCouriers()[$courier] ?? $courier,
            'client'   => isset($configs[$courier]) ? ExpressApiClient::create($courier, $configs[$courier]) : null,
        ];

        // 推断完整链路模板（用于展示「自动关联的物流链」）
        $chain->suggestedChain = self::suggestChainTemplate($configs);

        return $chain;
    }

    /**
     * 统一查询整条链路的轨迹（单段失败相互隔离）
     *
     * @param string|null $trackingNo 运单号（fromTracking 构建时可省略）
     * @param string      $language   轨迹语言
     * @return array ['legs' => [...], 'queried' => int, 'failed' => int]
     */
    public function track(?string $trackingNo = null, string $language = 'zh-CN'): array
    {
        $number = $trackingNo ?? ($this->request['tracking_no'] ?? '');
        if ($number === '') {
            throw new ExpressApiException('查询轨迹缺少运单号');
        }

        $legs = [];
        $queried = 0;
        $failed = 0;

        foreach ($this->legs as $leg) {
            if ($leg['client'] === null) {
                $legs[] = [
                    'leg'     => $leg['leg'],
                    'courier' => $leg['courier'],
                    'ok'      => false,
                    'error'   => '该环节未配置承运商，跳过查询',
                ];
                $failed++;
                continue;
            }
            try {
                $data = $leg['client']->queryTracking($number, $language);
                $queried++;
                $legs[] = [
                    'leg'     => $leg['leg'],
                    'courier' => $leg['courier'],
                    'ok'      => true,
                    'data'    => $data,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $legs[] = [
                    'leg'     => $leg['leg'],
                    'courier' => $leg['courier'],
                    'ok'      => false,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return ['legs' => $legs, 'queried' => $queried, 'failed' => $failed];
    }

    /**
     * 导出结构化信息（用于接口返回 / 调试）
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'request'          => $this->request,
            'detected_courier' => $this->detectedCourier,
            'suggested_chain'  => $this->suggestedChain,
            'legs'             => array_values(array_map(function ($leg) {
                return [
                    'leg'     => $leg['leg'],
                    'courier' => $leg['courier'],
                    'name'    => $leg['name'],
                    'ready'   => $leg['client'] !== null,
                ];
            }, $this->legs)),
        ];
    }

    /**
     * 解析单个环节的承运商：优先 $prefer → 默认推荐 → 同环节类目回退（均须已配置）
     *
     * @param string $leg
     * @param string $mode
     * @param array  $configs
     * @param array  $prefer
     * @return string|null
     */
    private static function resolveCourier(string $leg, string $mode, array $configs, array $prefer): ?string
    {
        $configured = static function (string $code) use ($configs): bool {
            return isset($configs[$code]) && ExpressApiClient::isCourierSupported($code);
        };

        // 1) 环节级显式覆盖
        if (isset($prefer[$leg]) && $configured($prefer[$leg])) {
            return strtolower($prefer[$leg]);
        }

        // 2) 默认推荐（跨境环节依运输方式选候选）
        if ($leg === self::LEG_CROSSBORDER) {
            foreach (self::$crossBorderByMode[$mode] ?? self::$crossBorderByMode['air'] as $code) {
                if ($configured($code)) {
                    return $code;
                }
            }
        } elseif (isset(self::$defaultChain[$leg]) && $configured(self::$defaultChain[$leg])) {
            return self::$defaultChain[$leg];
        }

        // 3) 同环节类目回退（取首个已配置者）
        foreach (self::$categoryFallback[$leg] ?? [] as $code) {
            if ($configured($code)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * 推断完整链路模板（仅列环节与推荐承运商，不实例化客户端）
     *
     * @param array $configs
     * @return array
     */
    private static function suggestChainTemplate(array $configs): array
    {
        $template = [
            self::LEG_PICKUP, self::LEG_LINEHAUL,
            self::LEG_CROSSBORDER, self::LEG_CUSTOMS, self::LEG_LASTMILE,
        ];
        $out = [];
        foreach ($template as $leg) {
            $courier = self::resolveCourier($leg, 'air', $configs, []);
            $out[] = [
                'leg'     => $leg,
                'courier' => $courier,
                'name'    => $courier !== null ? (ExpressApiClient::getSupportedCouriers()[$courier] ?? $courier) : null,
            ];
        }
        return $out;
    }
}
