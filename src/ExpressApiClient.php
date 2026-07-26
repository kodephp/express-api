<?php

namespace Kode\ExpressApi;

use Kode\ExpressApi\Common\ClientInterface;
use Kode\ExpressApi\EMS\Client as EMSClient;
use Kode\ExpressApi\EMS\Config as EMSConfig;
use Kode\ExpressApi\SF\Client as SFClient;
use Kode\ExpressApi\SF\Config as SFConfig;
use Kode\ExpressApi\Yunda\Client as YundaClient;
use Kode\ExpressApi\Yunda\Config as YundaConfig;
use Kode\ExpressApi\Zto\Client as ZtoClient;
use Kode\ExpressApi\Zto\Config as ZtoConfig;
use Kode\ExpressApi\Sto\Client as StoClient;
use Kode\ExpressApi\Sto\Config as StoConfig;
use Kode\ExpressApi\Cainiao\Client as CainiaoClient;
use Kode\ExpressApi\Cainiao\Config as CainiaoConfig;
use Kode\ExpressApi\FourPx\Client as FourPxClient;
use Kode\ExpressApi\FourPx\Config as FourPxConfig;
use Kode\ExpressApi\SfInternational\Client as SfInternationalClient;
use Kode\ExpressApi\SfInternational\Config as SfInternationalConfig;
use Kode\ExpressApi\Dhl\Client as DhlClient;
use Kode\ExpressApi\Dhl\Config as DhlConfig;
use Kode\ExpressApi\YunExpress\Client as YunExpressClient;
use Kode\ExpressApi\YunExpress\Config as YunExpressConfig;
use Kode\ExpressApi\EmsInternational\Client as EmsInternationalClient;
use Kode\ExpressApi\EmsInternational\Config as EmsInternationalConfig;
use Kode\ExpressApi\Yanwen\Client as YanwenClient;
use Kode\ExpressApi\Yanwen\Config as YanwenConfig;
use Kode\ExpressApi\Debang\Client as DebangClient;
use Kode\ExpressApi\Debang\Config as DebangConfig;
use Kode\ExpressApi\Ane\Client as AneClient;
use Kode\ExpressApi\Ane\Config as AneConfig;
use Kode\ExpressApi\Hoau\Client as HoauClient;
use Kode\ExpressApi\Hoau\Config as HoauConfig;
use Kode\ExpressApi\Jd\Client as JdClient;
use Kode\ExpressApi\Jd\Config as JdConfig;
use Kode\ExpressApi\Kuaidi100\Client as Kuaidi100Client;
use Kode\ExpressApi\Kuaidi100\Config as Kuaidi100Config;
use Kode\ExpressApi\Kuaidiniao\Client as KuaidiniaoClient;
use Kode\ExpressApi\Kuaidiniao\Config as KuaidiniaoConfig;
use Kode\ExpressApi\Juhe\Client as JuheClient;
use Kode\ExpressApi\Juhe\Config as JuheConfig;
use Kode\ExpressApi\SeventeenTrack\Client as SeventeenTrackClient;
use Kode\ExpressApi\SeventeenTrack\Config as SeventeenTrackConfig;
use Kode\ExpressApi\Fedex\Client as FedexClient;
use Kode\ExpressApi\Fedex\Config as FedexConfig;
use Kode\ExpressApi\Ups\Client as UpsClient;
use Kode\ExpressApi\Ups\Config as UpsConfig;
use Kode\ExpressApi\Jt\Client as JtClient;
use Kode\ExpressApi\Jt\Config as JtConfig;
use Kode\ExpressApi\Yto\Client as YtoClient;
use Kode\ExpressApi\Yto\Config as YtoConfig;
use Kode\ExpressApi\Common\CourierRecognizer;
use Kode\ExpressApi\Common\AbstractAggregatorClient;
use Kode\ExpressApi\Common\Resolver\AggregateResolver;
use Kode\ExpressApi\LogisticsChain\LogisticsChain;

/**
 * 通用快递API客户端
 * 
 * 提供统一的入口来创建和管理不同快递公司的API客户端
 */
class ExpressApiClient
{
    /**
     * 当前 SDK 版本号（语义化版本）
     *
     * @var string
     */
    public const VERSION = '2.5.0';

    /**
     * 支持的快递公司列表
     *
     * @var array
     */
    protected static $supportedCouriers = [
        'ems' => [
            'name' => '邮政EMS',
            'client' => EMSClient::class,
            'config' => EMSConfig::class
        ],
        'sf' => [
            'name' => '顺丰速运',
            'client' => SFClient::class,
            'config' => SFConfig::class
        ],
        'yunda' => [
            'name' => '韵达快递',
            'client' => YundaClient::class,
            'config' => YundaConfig::class
        ],
        'zto' => [
            'name' => '中通快递',
            'client' => ZtoClient::class,
            'config' => ZtoConfig::class
        ],
        'sto' => [
            'name' => '申通快递',
            'client' => StoClient::class,
            'config' => StoConfig::class
        ],
        'cainiao' => [
            'name' => '菜鸟网络',
            'client' => CainiaoClient::class,
            'config' => CainiaoConfig::class
        ],
        'fourpx' => [
            'name' => '4PX递四方',
            'client' => FourPxClient::class,
            'config' => FourPxConfig::class
        ],
        'sf_international' => [
            'name' => '顺丰国际',
            'client' => SfInternationalClient::class,
            'config' => SfInternationalConfig::class
        ],
        'dhl' => [
            'name' => 'DHL国际',
            'client' => DhlClient::class,
            'config' => DhlConfig::class
        ],
        'yunexpress' => [
            'name' => '云途物流',
            'client' => YunExpressClient::class,
            'config' => YunExpressConfig::class
        ],
        'ems_international' => [
            'name' => 'EMS国际',
            'client' => EmsInternationalClient::class,
            'config' => EmsInternationalConfig::class
        ],
        'yanwen' => [
            'name' => '燕文物流',
            'client' => YanwenClient::class,
            'config' => YanwenConfig::class
        ],
        'fedex' => [
            'name' => 'FedEx（联邦快递）国际',
            'client' => FedexClient::class,
            'config' => FedexConfig::class
        ],
        'ups' => [
            'name' => 'UPS（联合包裹）国际',
            'client' => UpsClient::class,
            'config' => UpsConfig::class
        ],
        'debang' => [
            'name' => '德邦物流',
            'client' => DebangClient::class,
            'config' => DebangConfig::class
        ],
        'ane' => [
            'name' => '安能物流',
            'client' => AneClient::class,
            'config' => AneConfig::class
        ],
        'hoau' => [
            'name' => '天地华宇',
            'client' => HoauClient::class,
            'config' => HoauConfig::class
        ],
        'jd' => [
            'name' => '京东快递/京东物流',
            'client' => JdClient::class,
            'config' => JdConfig::class
        ],
        'jt' => [
            'name' => '极兔速递（J&T）',
            'client' => JtClient::class,
            'config' => JtConfig::class
        ],
        'yto' => [
            'name' => '圆通速递（YTO）',
            'client' => YtoClient::class,
            'config' => YtoConfig::class
        ],
        'kuaidi100' => [
            'name' => '快递100（聚合查询）',
            'client' => Kuaidi100Client::class,
            'config' => Kuaidi100Config::class
        ],
        'kuaidiniao' => [
            'name' => '快递鸟（聚合查询）',
            'client' => KuaidiniaoClient::class,
            'config' => KuaidiniaoConfig::class
        ],
        'juhe' => [
            'name' => '聚合数据（聚合查询）',
            'client' => JuheClient::class,
            'config' => JuheConfig::class
        ],
        'seventeentrack' => [
            'name' => '17TRACK（国际运单识别）',
            'client' => SeventeenTrackClient::class,
            'config' => SeventeenTrackConfig::class
        ]
    ];

    /**
     * API 操作目录（方法名 => 元数据）
     *
     * 集中描述 SDK 支持的全部快递 API 操作及其分类，供 getApiMenu() 生成能力菜单。
     * 各快递商实际支持的操作通过反射（method_exists）动态发现，无需在此逐个标注。
     *
     * @var array
     */
    private const OPERATIONS = [
        // 下单 / 订单
        'sendShipment'       => ['label' => '下单/发货',     'category' => 'order'],
        'batchSendShipment'  => ['label' => '批量下单',       'category' => 'order'],
        'createOrder'        => ['label' => '创建订单',       'category' => 'order'],
        'batchCreateOrder'   => ['label' => '批量创建订单',   'category' => 'order'],
        'cancelOrder'        => ['label' => '取消订单',       'category' => 'order'],
        'intercept'          => ['label' => '拦截件',         'category' => 'order'],
        'interceptOrder'     => ['label' => '拦截件(订单级)', 'category' => 'order'],
        'modify'             => ['label' => '改件信息',       'category' => 'order'],
        'updateOrderInfo'    => ['label' => '更新订单信息',   'category' => 'order'],
        'pickupNotice'       => ['label' => '揽收通知',       'category' => 'order'],
        'createPickup'       => ['label' => '创建揽收',       'category' => 'order'],
        // 查询
        'queryOrder'             => ['label' => '订单查询',         'category' => 'query'],
        'batchQueryOrders'       => ['label' => '批量订单查询',     'category' => 'query'],
        'queryTracking'          => ['label' => '轨迹查询',         'category' => 'query'],
        'batchQueryTracking'     => ['label' => '批量轨迹查询',     'category' => 'query'],
        'queryTrackingWithCourier' => ['label' => '指定快递商轨迹查询', 'category' => 'query'],
        // 面单
        'printLabel'        => ['label' => '面单打印',       'category' => 'label'],
        'batchPrintLabels'  => ['label' => '批量面单打印',   'category' => 'label'],
        'getLabelTemplate'  => ['label' => '获取面单模板',   'category' => 'label'],
        'printWaybill'      => ['label' => '打印面单',       'category' => 'label'],
        'getWaybillBalance' => ['label' => '查询面单余额',   'category' => 'label'],
        // 国际物流（海运 / 空运）
        'createSeaFreight'  => ['label' => '海运下单',       'category' => 'freight'],
        'createAirFreight'  => ['label' => '空运下单',       'category' => 'freight'],
        'getQuotation'      => ['label' => '运费报价',       'category' => 'freight'],
        'declareCustoms'    => ['label' => '海关申报',       'category' => 'customs'],
        'queryCustoms'      => ['label' => '清关查询',       'category' => 'customs'],
        // 国内货运（零担 / 整车 / 快运）
        'createLtl'         => ['label' => '零担下单',       'category' => 'freight'],
        'createFtl'         => ['label' => '整车下单',       'category' => 'freight'],
        'queryNetwork'      => ['label' => '网点查询',       'category' => 'freight'],
        'cargoInsurance'    => ['label' => '货物保价',       'category' => 'freight'],
    ];

    /**
     * 创建快递客户端实例
     *
     * @param string $courier 快递公司标识 (如: 'ems', 'sf', 'yt', 'zt')
     * @param array|object $config 配置信息，可以是数组或对应的配置对象
     * @param array $options 额外选项
     * @return ClientInterface
     * @throws \InvalidArgumentException
     */
    public static function create(string $courier, $config, array $options = []): ClientInterface
    {
        $courier = strtolower($courier);
        
        // 检查快递公司是否支持
        if (!isset(self::$supportedCouriers[$courier])) {
            throw new \InvalidArgumentException(
                "不支持的快递公司: {$courier}。支持的快递公司有: " . 
                implode(', ', array_keys(self::$supportedCouriers))
            );
        }

        $courierInfo = self::$supportedCouriers[$courier];
        $clientClass = $courierInfo['client'];
        $configClass = $courierInfo['config'];

        // 确保配置是正确类型
        if (is_array($config)) {
            // 如果是数组，创建对应的配置对象
            $config = new $configClass($config);
        }

        // 验证配置类型
        if (!$config instanceof $configClass) {
            throw new \InvalidArgumentException(
                "{$courierInfo['name']}客户端需要有效的{$configClass}配置对象"
            );
        }

        try {
            // 创建并返回客户端实例
            $client = new $clientClass($config);
            
            // 应用额外选项（如果有）
            if (!empty($options)) {
                self::applyOptions($client, $options);
            }
            
            return $client;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                "创建{$courierInfo['name']}客户端失败: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * 获取支持的快递公司列表
     *
     * @return array 支持的快递公司信息
     */
    public static function getSupportedCouriers(): array
    {
        $result = [];
        foreach (self::$supportedCouriers as $code => $info) {
            $result[$code] = $info['name'];
        }
        return $result;
    }

    /**
     * 获取 API 能力菜单（快递商 -> 可用操作目录）
     *
     * 返回所有（或指定）快递商支持的 API 操作，按业务分类（order/query/label）分组，
     * 便于调用方自动发现能力、生成文档或前端动态渲染菜单。
     *
     * 各快递商实际支持的操作通过反射（method_exists）基于其 Client 类动态判定，
     * 因此新增/调整 Client 方法后此处无需手动维护。
     *
     * @param string|null $courier 指定快递商代码（如 'ems'）；为 null 时返回全部
     * @return array
     * @throws \InvalidArgumentException
     */
    public static function getApiMenu(?string $courier = null): array
    {
        if ($courier !== null) {
            $courier = strtolower($courier);
            if (!isset(self::$supportedCouriers[$courier])) {
                throw new \InvalidArgumentException(
                    "不支持的快递公司: {$courier}。支持的快递公司有: " .
                    implode(', ', array_keys(self::$supportedCouriers))
                );
            }
            $codes = [$courier];
        } else {
            $codes = array_keys(self::$supportedCouriers);
        }

        $menu = [
            'version'  => self::VERSION,
            'couriers' => [],
        ];

        foreach ($codes as $code) {
            $info = self::$supportedCouriers[$code];
            $clientClass = $info['client'];

            $grouped = [];
            foreach (self::OPERATIONS as $method => $meta) {
                if (!method_exists($clientClass, $method)) {
                    continue;
                }
                $grouped[$meta['category']][$method] = ['label' => $meta['label']];
            }
            ksort($grouped);

            $menu['couriers'][$code] = [
                'name'           => $info['name'],
                'operation_count' => array_sum(array_map('count', $grouped)),
                'operations'     => $grouped,
            ];
        }

        return $menu;
    }

    /**
     * 获取完整的 API 操作目录（与具体快递商无关）
     *
     * @return array
     */
    public static function getOperationCatalog(): array
    {
        return self::OPERATIONS;
    }

    /**
     * 获取当前 SDK 版本号
     *
     * @return string
     */
    public static function version(): string
    {
        return self::VERSION;
    }

    /**
     * 运单号自动识别归属承运商（委托 CourierRecognizer）
     *
     * 解决「不用自己去指定物流链的物流」：仅凭运单号即可推断其归属的
     * 快递 / 货运 / 国际物流服务商。规则未命中且已配置聚合解析器时，
     * 会自动回退到 17TRACK / 快递100 / 快递鸟 / 聚合数据等权威解析。
     *
     * 推荐用法：先用 buildAggregateResolver() 聚合已签约的聚合查询服务商，
     * 再将返回的解析器传入本方法，即可获得「确定性」识别：
     * ```php
     * $resolver = ExpressApiClient::buildAggregateResolver([
     *     'seventeentrack' => ['app_secret' => 'TOKEN'],
     *     'kuaidi100'      => ['app_key' => 'K', 'app_secret' => 'S'],
     * ]);
     * $courier = ExpressApiClient::recognize($trackingNo, $resolver);
     * ```
     *
     * @param string        $trackingNo 运单号
     * @param callable|null $resolver   可选：本次调用使用的解析器（覆盖全局设置）
     * @return string|null 命中返回承运商代码（如 'sf'），未命中返回 null
     */
    public static function recognize(string $trackingNo, ?callable $resolver = null): ?string
    {
        return CourierRecognizer::detect($trackingNo, $resolver);
    }

    /**
     * 构建聚合解析器（将多家已签约的聚合查询服务商聚合为权威识别源）
     *
     * 仅接纳本 SDK 已支持且属于聚合查询客户端（AbstractAggregatorClient）的承运商，
     * 配置无效或实例化失败时自动跳过，不会中断构建。
     *
     * @param array $aggregators 聚合商配置，键为承运商代码，值为配置数组
     *                           例如 ['seventeentrack' => ['app_secret' => 'TOKEN']]
     * @return AggregateResolver
     */
    public static function buildAggregateResolver(array $aggregators): AggregateResolver
    {
        $resolver = new AggregateResolver();

        foreach ($aggregators as $code => $config) {
            $code = strtolower((string) $code);
            if (!self::isCourierSupported($code)) {
                continue;
            }

            $info = self::$supportedCouriers[$code];
            if (!is_subclass_of($info['client'], AbstractAggregatorClient::class)) {
                continue;
            }

            try {
                $client = self::create($code, (array) $config);
                $resolver->add($client);
            } catch (\Throwable $e) {
                // 配置无效则跳过该聚合商
                continue;
            }
        }

        return $resolver;
    }

    /**
     * 按发货意图自动编排物流链（委托 LogisticsChain）
     *
     * 给定起止国家 / 重量 / 运输方式，自动挑选每个环节
     * （揽收 → 干线 → 跨境 → 清关 → 末端）的承运商并拼装整条链路，
     * 无需逐段指定。可用 $prefer 覆盖某个环节的推荐承运商。
     *
     * @param array $request 发货意图（origin/dest/weight/mode）
     * @param array $configs 已签约承运商配置（键为承运商代码）
     * @param array $prefer  环节级承运商覆盖（如 ['crossborder' => 'fourpx']）
     * @return LogisticsChain
     */
    public static function buildChain(array $request, array $configs, array $prefer = []): LogisticsChain
    {
        return LogisticsChain::compose($request, $configs, $prefer);
    }

    /**
     * 按运单号自动识别并推断完整物流链（委托 LogisticsChain）
     *
     * @param string $trackingNo 运单号
     * @param array  $configs    已签约承运商配置
     * @return LogisticsChain
     */
    public static function chainFromTracking(string $trackingNo, array $configs): LogisticsChain
    {
        return LogisticsChain::fromTracking($trackingNo, $configs);
    }

    /**
     * 批量轨迹查询（跨快递商聚合）
     *
     * 接收一组「快递商 + 运单号」条目，逐单调用对应客户端的 queryTracking，
     * 单条失败相互隔离（不中断其余查询），最终汇总为 results / success / failed。
     *
     * @param array  $items    查询条目列表，每条：['courier' => 'ems', 'number' => '运单号']
     * @param string $language 轨迹语言（zh-CN, en-US），透传给各客户端
     * @return array ['results' => [...], 'success' => int, 'failed' => int]
     * @throws \InvalidArgumentException
     */
    public static function batchQueryTracking(array $items, string $language = 'zh-CN'): array
    {
        if (empty($items)) {
            throw new \InvalidArgumentException('批量轨迹查询条目不能为空');
        }

        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($items as $index => $item) {
            $courier = strtolower((string) ($item['courier'] ?? ''));
            $number = (string) ($item['number'] ?? '');

            if ($courier === '' || $number === '') {
                $failed++;
                $results[] = [
                    'index'   => $index,
                    'courier' => $courier,
                    'number'  => $number,
                    'ok'      => false,
                    'error'   => '条目缺少 courier 或 number',
                ];
                continue;
            }

            if (!self::isCourierSupported($courier)) {
                $failed++;
                $results[] = [
                    'index'   => $index,
                    'courier' => $courier,
                    'number'  => $number,
                    'ok'      => false,
                    'error'   => "不支持的快递公司: {$courier}",
                ];
                continue;
            }

            try {
                $client = self::create($courier, []);
                $data = $client->queryTracking($number, $language);
                $success++;
                $results[] = [
                    'index'   => $index,
                    'courier' => $courier,
                    'number'  => $number,
                    'ok'      => true,
                    'data'    => $data,
                ];
            } catch (\Throwable $e) {
                $failed++;
                $results[] = [
                    'index'   => $index,
                    'courier' => $courier,
                    'number'  => $number,
                    'ok'      => false,
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return [
            'results' => $results,
            'success' => $success,
            'failed'  => $failed,
        ];
    }

    /**
     * 检查快递公司是否支持
     *
     * @param string $courier 快递公司标识
     * @return bool 是否支持
     */
    public static function isCourierSupported(string $courier): bool
    {
        return isset(self::$supportedCouriers[strtolower($courier)]);
    }

    /**
     * 获取快递公司的详细信息
     *
     * @param string $courier 快递公司标识
     * @return array|null 快递公司信息
     */
    public static function getCourierInfo(string $courier): ?array
    {
        $courier = strtolower($courier);
        return self::$supportedCouriers[$courier] ?? null;
    }

    /**
     * 应用额外选项到客户端
     *
     * @param ClientInterface $client 客户端实例
     * @param array $options 选项数组
     * @return void
     */
    protected static function applyOptions(ClientInterface $client, array $options): void
    {
        // 这里可以根据需要扩展选项处理逻辑
        // 例如设置调试模式、日志处理器等
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($client, $method)) {
                $client->$method($value);
            }
        }
    }
}
