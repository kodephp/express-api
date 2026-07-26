<?php

namespace Kode\ExpressApi\Tests\Kuaidiniao;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\Kuaidiniao\Client;
use PHPUnit\Framework\TestCase;

/**
 * 快递鸟 聚合查询客户端测试
 */
class ClientTest extends TestCase
{
    private function makeClient(): Client
    {
        return ExpressApiClient::create('kuaidiniao', ['app_key' => 'k', 'app_secret' => 's']);
    }

    public function testQueryTrackingThrowsWhenNumberEmpty(): void
    {
        $this->expectException(ExpressApiException::class);
        $this->makeClient()->queryTracking('');
    }

    public function testRecognizeTrackingThrowsWhenNumberEmpty(): void
    {
        $this->expectException(ExpressApiException::class);
        $this->makeClient()->recognizeTracking('');
    }

    public function testUnsupportedMethodsThrow(): void
    {
        $client = $this->makeClient();
        $this->expectException(ExpressApiException::class);
        $client->printLabel('123');
    }

    public function testRealQueryRequiresNetwork(): void
    {
        $this->markTestIncomplete('真实轨迹查询需快递鸟凭证，避免触网；结构 / 校验见其他用例');
        $this->makeClient()->queryTracking('SF1234567890123');
    }
}
