<?php

namespace Kode\ExpressApi\Common;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 通用响应处理器
 *
 * 作为全仓库响应归一化的唯一入口，通过「按 provider 注册策略」的机制，
 * 让每家快递商可以用与其真实接口一致的规则判定成功/失败并解包业务数据，
 * 避免散落在各 Client 的重复、易错的判断逻辑：
 *
 *  - registerPolicy(provider, isError, unwrap)：为指定快递商注册错误判定与数据解包闭包；
 *  - 未注册的快递商回退到保守的默认策略（仅在明确错误信号下抛错，不擅自改包结构）；
 *  - EMS / 顺丰 等已在类加载时预置精确策略，保持历史行为不变。
 *
 * 策略闭包签名：
 *  - isError(array $response): bool       —— 返回 true 表示业务失败，应抛 ExpressApiException
 *  - unwrap(array $response): array       —— 返回解包后的业务数据（成功时调用）
 */
class ResponseHandler
{
    /**
     * 各快递商响应处理策略：[provider => ['isError' => callable, 'unwrap' => callable]]
     *
     * @var array
     */
    private static array $policies = [];

    /**
     * 是否已预置内置策略（懒加载，避免重复注册）
     *
     * @var bool
     */
    private static bool $seeded = false;

    /**
     * 处理 API 响应（统一入口）
     *
     * @param array  $response 原始响应数据
     * @param string $courier  快递公司标识（provider）
     * @return array 标准化后的响应数据
     * @throws ExpressApiException
     */
    public static function handle(array $response, string $courier): array
    {
        self::ensureSeeded();

        $provider = strtolower($courier);
        $policy = self::$policies[$provider] ?? self::defaultPolicy();

        if (($policy['isError'])($response)) {
            throw self::buildException($response, $courier);
        }

        return ($policy['unwrap'])($response);
    }

    /**
     * 为指定快递商注册响应处理策略
     *
     * @param string   $provider 快递公司标识（provider，大小写不敏感）
     * @param callable $isError  错误判定闭包：fn(array $response): bool
     * @param callable $unwrap   数据解包闭包：fn(array $response): array
     * @return void
     */
    public static function registerPolicy(string $provider, callable $isError, callable $unwrap): void
    {
        self::$policies[strtolower($provider)] = [
            'isError' => $isError,
            'unwrap'  => $unwrap,
        ];
    }

    /**
     * 获取指定快递商的响应处理策略
     *
     * @param string $provider
     * @return array|null
     */
    public static function getPolicy(string $provider): ?array
    {
        self::ensureSeeded();
        return self::$policies[strtolower($provider)] ?? null;
    }

    /**
     * 懒加载预置内置策略（EMS / 顺丰等）
     *
     * @return void
     */
    private static function ensureSeeded(): void
    {
        if (self::$seeded) {
            return;
        }

        // 邮政 EMS：success 为布尔，data 为业务数据
        self::registerPolicy('ems',
            static fn(array $r): bool => isset($r['success']) && !$r['success'],
            static fn(array $r): array => $r['data'] ?? $r
        );

        // 顺丰：error 字段或 status !== 'success' 视为失败；成功时解包 data
        self::registerPolicy('sf',
            static fn(array $r): bool => isset($r['error'])
                || (isset($r['status']) && is_string($r['status']) && strtolower($r['status']) !== 'success'),
            static fn(array $r): array => (isset($r['data']) && isset($r['status']) && strtolower($r['status']) === 'success')
                ? $r['data']
                : $r
        );

        self::$seeded = true;
    }

    /**
     * 默认（保守）响应处理策略
     *
     * 仅在明确错误信号下判定失败，避免误伤使用 code=0/200 表示成功的接口；
     * 成功时原样返回，不擅自改包结构（各快递商如需解包应注册专属策略）。
     *
     * @return array
     */
    private static function defaultPolicy(): array
    {
        return [
            'isError' => static function (array $r): bool {
                if (isset($r['error'])) {
                    return true;
                }
                if (isset($r['success']) && $r['success'] !== true && $r['success'] !== 1 && $r['success'] !== 'true') {
                    return true;
                }
                if (isset($r['code']) && is_numeric($r['code'])) {
                    $code = (int) $r['code'];
                    if ($code >= 400 && $code <= 599) {
                        return true;
                    }
                }
                return false;
            },
            'unwrap' => static fn(array $r): array => $r,
        ];
    }

    /**
     * 根据响应构建标准化异常
     *
     * @param array  $response
     * @param string $courier
     * @return ExpressApiException
     */
    private static function buildException(array $response, string $courier): ExpressApiException
    {
        $message = 'API调用失败';
        $code = 0;

        if (isset($response['error']['message'])) {
            $message = (string) $response['error']['message'];
            $code = (int) ($response['error']['code'] ?? 0);
        } elseif (isset($response['message'])) {
            $message = (string) $response['message'];
            $code = (int) ($response['code'] ?? 0);
        } elseif (isset($response['errorMsg'])) {
            $message = (string) $response['errorMsg'];
        } elseif (isset($response['msg'])) {
            $message = (string) $response['msg'];
        }

        // 将 provider 信息并入异常，便于排查
        $message = sprintf('[%s] %s', strtolower($courier), $message);

        return new ExpressApiException($message, $code, null, $response);
    }
}
