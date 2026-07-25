<?php

namespace Kode\ExpressApi\Common;

use Kode\ExpressApi\Common\Exception\ExpressApiException;

/**
 * 快递公司通用客户端基类
 *
 * 统一封装构造函数、配置 / 认证对象的持有与暴露，以及底层 HTTP 请求管道
 * （认证头注入、通用响应处理、异常归一化）。各快递公司只需声明 provider 标识、
 * 配置类与认证类，并实现各自差异化的业务方法，即可消除大量重复代码，
 * 同时让鉴权、超时、SSL 与错误处理等行为在单一位置保持一致、健壮、可审计。
 */
abstract class AbstractCourierClient implements ClientInterface
{
    /**
     * @var AbstractCourierConfig 配置信息
     */
    protected AbstractCourierConfig $config;

    /**
     * @var AuthInterface 认证对象（各快递商鉴权参数存在差异，统一面向接口编程）
     */
    protected AuthInterface $auth;

    /**
     * 业务 provider 标识（用于 ResponseHandler 区分各快递公司响应结构）
     *
     * @return string
     */
    abstract protected function getProvider(): string;

    /**
     * 具体配置类的完全限定名（用于构造配置对象）
     *
     * @return string
     */
    abstract protected function getConfigClass(): string;

    /**
     * 具体认证类的完全限定名（用于构造认证对象）
     *
     * @return string
     */
    abstract protected function getAuthClass(): string;

    /**
     * 构造函数
     *
     * @param array|AbstractCourierConfig $config 配置信息
     * @throws \InvalidArgumentException
     */
    public function __construct($config = [])
    {
        $configClass = $this->getConfigClass();

        if (is_array($config)) {
            $this->config = new $configClass($config);
        } elseif ($config instanceof $configClass) {
            $this->config = $config;
        } else {
            throw new \InvalidArgumentException('配置信息必须是数组或 ' . $configClass . ' 对象');
        }

        $authClass = $this->getAuthClass();
        $this->auth = new $authClass($this->config);
    }

    /**
     * 获取配置对象
     *
     * @return AbstractCourierConfig
     */
    public function getConfig(): AbstractCourierConfig
    {
        return $this->config;
    }

    /**
     * 获取认证对象
     *
     * @return AuthInterface
     */
    public function getAuth(): AuthInterface
    {
        return $this->auth;
    }

    /**
     * 发送 HTTP 请求（统一管道）
     *
     * @param string $method HTTP 方法
     * @param string $uri 请求 URI
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array
     * @throws ExpressApiException
     */
    protected function request(string $method, string $uri, array $data = [], array $headers = []): array
    {
        $url = $this->config->getBaseUrl() . $uri;

        // 统一注入认证头与内容类型，避免各客户端散落重复逻辑
        $headers['Authorization'] = 'Bearer ' . $this->auth->getAccessToken();
        $headers['Content-Type'] = 'application/json';
        $headers = $this->prepareRequestHeaders($headers);

        try {
            // 使用通用 HTTP 客户端发送请求（已默认强制 SSL 证书校验）
            $response = HttpClient::request(
                $method,
                $url,
                $data,
                $headers,
                $this->config->getTimeout()
            );

            // 使用通用响应处理器处理响应
            return ResponseHandler::handle($response, $this->getProvider());
        } catch (\Exception $e) {
            if ($e instanceof ExpressApiException) {
                throw $e;
            }
            throw new ExpressApiException('请求失败: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 请求发送前调整请求头，子类可重写以附加特定请求头（如菜鸟的 X-Partner-Id）。
     * 默认不做任何修改。
     *
     * @param array $headers
     * @return array
     */
    protected function prepareRequestHeaders(array $headers): array
    {
        return $headers;
    }
}
