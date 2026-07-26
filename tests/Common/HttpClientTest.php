<?php

namespace Kode\ExpressApi\Tests\Common;

use Kode\ExpressApi\Common\HttpClient;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use PHPUnit\Framework\TestCase;

/**
 * HTTP 客户端韧性测试
 *
 * 覆盖：协议白名单、重试配置、诊断元信息、重试次数（对不可达地址验证）。
 * 重试延迟较短，仅验证「重试发生且最终抛错」的行为，不依赖真实网络调用。
 */
class HttpClientTest extends TestCase
{
    protected function setUp(): void
    {
        // 恢复默认全局策略，避免测试间相互污染
        HttpClient::setRetry(0, 200);
        HttpClient::setVerifySsl(true);
    }

    protected function tearDown(): void
    {
        HttpClient::setRetry(0, 200);
        HttpClient::setVerifySsl(true);
    }

    public function testProtocolWhitelistRejectsIllegalScheme(): void
    {
        $this->expectException(ExpressApiException::class);
        HttpClient::request('GET', 'ftp://example.com/x');
    }

    public function testSetRetryAndVerifySslAreConfigurable(): void
    {
        HttpClient::setRetry(3, 50);
        HttpClient::setVerifySsl(false);

        // 配置本身不抛错即为生效；通过不可达地址触发请求以走完整分支
        try {
            HttpClient::request('GET', 'http://127.0.0.1:1/none', [], [], 1);
        } catch (ExpressApiException $e) {
            // 预期：连接失败
        }

        $meta = HttpClient::getLastMeta();
        $this->assertEquals('http://127.0.0.1:1/none', $meta['url']);
        $this->assertEquals('GET', $meta['method']);
        $this->assertGreaterThanOrEqual(1, $meta['attempt']);
    }

    public function testRetriesOnConnectionFailureThenThrows(): void
    {
        // 连接被拒的地址：应重试 2 次（共 3 次尝试）后抛错
        HttpClient::setRetry(2, 1);

        $this->expectException(ExpressApiException::class);
        HttpClient::request('GET', 'http://127.0.0.1:1/unreachable', [], [], 1);
    }

    public function testRetryAttemptCountReportedInMeta(): void
    {
        HttpClient::setRetry(2, 1);

        try {
            HttpClient::request('GET', 'http://127.0.0.1:1/unreachable', [], [], 1);
        } catch (ExpressApiException $e) {
            // ignore
        }

        $meta = HttpClient::getLastMeta();
        // 首次 + 2 次重试 = 3 次尝试
        $this->assertEquals(3, $meta['attempt']);
        $this->assertGreaterThanOrEqual(0, $meta['duration_ms']);
    }

    public function testNoRetryByDefault(): void
    {
        HttpClient::setRetry(0, 1);

        try {
            HttpClient::request('GET', 'http://127.0.0.1:1/unreachable', [], [], 1);
        } catch (ExpressApiException $e) {
            // ignore
        }

        $meta = HttpClient::getLastMeta();
        $this->assertEquals(1, $meta['attempt']);
    }

    public function testLastMetaShapeWhenNoRequestYet(): void
    {
        // 进程级 lastMeta 已在其它用例写入，这里仅校验字段完整性
        $meta = HttpClient::getLastMeta();
        $this->assertArrayHasKey('url', $meta);
        $this->assertArrayHasKey('method', $meta);
        $this->assertArrayHasKey('http_code', $meta);
        $this->assertArrayHasKey('attempt', $meta);
        $this->assertArrayHasKey('duration_ms', $meta);
    }
}
