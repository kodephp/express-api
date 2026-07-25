<?php

namespace Kode\ExpressApi\Common;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 通用 HTTP 客户端（基于 cURL）
 *
 * 作为全仓库唯一的 HTTP 传输实现，统一处理：
 * - 默认开启 SSL 证书校验，防止中间人攻击；
 * - 支持 JSON 与 form-urlencoded 两种请求体；
 * - 请求地址协议白名单校验；
 * - 超时、错误信息标准化。
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
     * 发送 HTTP 请求
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

        $curl = curl_init();

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => max(1, $timeout),
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
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error !== '') {
            throw new ExpressApiException('请求失败: ' . $error);
        }

        if ($response === false) {
            throw new ExpressApiException('请求失败: 未收到响应');
        }

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new ExpressApiException(
                'HTTP 请求失败: ' . ($result['message'] ?? '未知错误'),
                $httpCode,
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
