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
    public const VERSION = '1.3.0';

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
        ]
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
