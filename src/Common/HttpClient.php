<?php

namespace Kode\ExpressApi\Common;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 通用 HTTP 客户端（基于 cURL）
 *
 * 作为全仓库唯一的 HTTP 传输实现，统一处理：
 * - 默认开启 SSL 证书校验，防止中间人攻击；
 * - 支持 JSON 与 form-urlencoded 两种请求体；
 * - 请求地址协议白名单校验，规避 SSRF / 非法地址；
 * - 指数退避重试（仅对瞬时故障：连接错误、超时、HTTP 5xx 重试；4xx 视为终态不重试）；
 * - 超时、错误信息标准化，并提供最近一次请求的诊断元信息（getLastMeta）。
 */
class HttpClient
{
    /**
     * 是否校验 SSL 证书（默认开启）
     *
     * @var bool
     */
    private static bool $verifySsl = true;

    /**
     * 允许的请求协议
     *
     * @var array
     */
    private static array $allowedSchemes = ['http', 'https'];

    /**
     * 失败重试次数（不含首次，默认 0 即不重试）
     *
     * @var int
     */
    private static int $maxRetries = 0;

    /**
     * 重试基础延迟（毫秒），实际延迟 = base * 2^(attempt-1)
     *
     * @var int
     */
    private static int $retryBaseDelayMs = 200;

    /**
     * 最近一次请求的诊断元信息
     *
     * @var array|null
     */
    private static ?array $lastMeta = null;

    /**
     * 最近一次请求的 HTTP 状态码（doRequest 内部写入）
     *
     * @var int
     */
    private static int $lastHttpCode = 0;

    /**
     * 设置是否校验 SSL 证书
     *
     * @param bool $verify
     * @return void
     */
    public static function setVerifySsl(bool $verify): void
    {
        self::$verifySsl = $verify;
    }

    /**
     * 配置失败重试策略
     *
     * 仅对瞬时故障重试：连接错误（curl error）、超时、HTTP 5xx。
     * 客户端错误（HTTP 4xx）与 ExpressApiException 视为终态，不重试。
     *
     * @param int $attempts        额外重试次数（不含首次），0 表示关闭重试
     * @param int $baseDelayMs     基础退避延迟（毫秒），实际延迟按指数增长
     * @return void
     */
    public static function setRetry(int $attempts, int $baseDelayMs = 200): void
    {
        self::$maxRetries = max(0, $attempts);
        self::$retryBaseDelayMs = max(0, $baseDelayMs);
    }

    /**
     * 获取最近一次请求的诊断元信息
     *
     * 便于在不引入日志依赖的前提下排查问题（如接口耗时、HTTP 状态码、重试次数）。
     * 字段：url, method, http_code, attempt（含首次的总尝试次数）, duration_ms。
     * 若尚未发起过请求，返回全空结构。
     *
     * @return array
     */
    public static function getLastMeta(): array
    {
        return self::$lastMeta ?? [
            'url'         => '',
            'method'      => '',
            'http_code'   => 0,
            'attempt'     => 0,
            'duration_ms' => 0,
        ];
    }

    /**
     * 发送 HTTP 请求（带重试与诊断）
     *
     * @param string $method  HTTP 方法
     * @param string $url     请求 URL
     * @param array  $data    请求数据
     * @param array  $headers 请求头
     * @param int    $timeout 超时时间（秒）
     * @return array
     * @throws ExpressApiException
     */
    public static function request(
        string $method,
        string $url,
        array $data = [],
        array $headers = [],
        int $timeout = 30
    ): array {
        // 协议白名单校验，避免 SSRF / 非法地址
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === false || !in_array(strtolower($scheme), self::$allowedSchemes, true)) {
            throw new ExpressApiException('非法的请求地址: ' . $url);
        }

        $start = microtime(true);
        $attempt = 0;
        $maxAttempts = self::$maxRetries + 1;

        $lastException = null;

        while ($attempt < $maxAttempts) {
            $attempt++;
            $lastException = null;

            try {
                $result = self::doRequest($method, $url, $data, $headers, $timeout);
                self::$lastMeta = [
                    'url'         => $url,
                    'method'      => strtoupper($method),
                    'http_code'   => self::$lastHttpCode,
                    'attempt'     => $attempt,
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                ];
                return $result;
            } catch (ExpressApiException $e) {
                $lastException = $e;

                // 4xx 客户端错误：终态，不重试
                if (self::$lastHttpCode >= 400 && self::$lastHttpCode < 500) {
                    break;
                }
                // 其他（连接错误 / 超时 / 5xx）：可重试
                if ($attempt < $maxAttempts && self::$retryBaseDelayMs > 0) {
                    usleep((int) (self::$retryBaseDelayMs * (2 ** ($attempt - 1)) * 1000));
                }
            }
        }

        self::$lastMeta = [
            'url'         => $url,
            'method'      => strtoupper($method),
            'http_code'   => self::$lastHttpCode,
            'attempt'     => $attempt,
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
        ];

        throw $lastException ?? new ExpressApiException('请求失败: 未知错误');
    }

    /**
     * 单次 HTTP 请求执行（不含重试）
     *
     * @param string $method
     * @param string $url
     * @param array  $data
     * @param array  $headers
     * @param int    $timeout
     * @return array
     * @throws ExpressApiException
     */
    private static function doRequest(
        string $method,
        string $url,
        array $data,
        array $headers,
        int $timeout
    ): array {
        $curl = curl_init();

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => max(1, $timeout),
            CURLOPT_CONNECTTIMEOUT => max(1, $timeout),
            CURLOPT_HTTPHEADER     => self::formatHeaders($headers),
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_SSL_VERIFYPEER => self::$verifySsl,
            CURLOPT_SSL_VERIFYHOST => self::$verifySsl ? 2 : 0,
            CURLOPT_USERAGENT      => 'kode-express-api',
        ];

        $methodUpper = strtoupper($method);
        if (!empty($data) && in_array($methodUpper, ['POST', 'PUT', 'PATCH'], true)) {
            if (isset($headers['Content-Type']) && stripos($headers['Content-Type'], 'application/x-www-form-urlencoded') !== false) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);
        self::$lastHttpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error !== '') {
            throw new ExpressApiException('请求失败: ' . $error);
        }

        if ($response === false) {
            throw new ExpressApiException('请求失败: 未收到响应');
        }

        $result = json_decode($response, true);

        if (self::$lastHttpCode >= 400) {
            throw new ExpressApiException(
                'HTTP 请求失败: ' . ($result['message'] ?? '未知错误'),
                self::$lastHttpCode,
                $result
            );
        }

        return $result ?: [];
    }

    /**
     * 格式化请求头
     *
     * @param array $headers 请求头数组
     * @return array
     */
    protected static function formatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = $key . ': ' . $value;
        }

        return $formatted;
    }
}
