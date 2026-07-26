<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\ExpressApiClient;
use PHPUnit\Framework\TestCase;

/**
 * ExpressApiClient 批量轨迹与版本入口测试
 */
class ExpressApiClientBatchTest extends TestCase
{
    public function testVersionReturnsCurrent(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', ExpressApiClient::version());
        $this->assertEquals(ExpressApiClient::VERSION, ExpressApiClient::version());
    }

    public function testBatchQueryTrackingRejectsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ExpressApiClient::batchQueryTracking([]);
    }

    public function testBatchQueryTrackingReportsMissingFields(): void
    {
        $result = ExpressApiClient::batchQueryTracking([
            ['courier' => '', 'number' => ''],
            ['courier' => 'nope', 'number' => '123'],
        ]);

        $this->assertArrayHasKey('results', $result);
        $this->assertEquals(2, $result['failed']);
        $this->assertEquals(0, $result['success']);
        $this->assertCount(2, $result['results']);
        $this->assertFalse($result['results'][0]['ok']);
        $this->assertFalse($result['results'][1]['ok']);
    }

    /**
     * 真实跨快递商轨迹查询需要网络与有效凭证，标记为 Incomplete 不触网，
     * 仅验证批量入口的聚合结构与失败隔离设计。
     */
    public function testBatchQueryTrackingAggregatesPerItem(): void
    {
        $this->markTestIncomplete('批量轨迹查询需网络与有效凭证，离线仅验证聚合结构');
    }
}
