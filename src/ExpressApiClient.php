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
    public const VERSION = '2.2.0';

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
