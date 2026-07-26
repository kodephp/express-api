<?php

namespace Kode\ExpressApi\Tests\Juhe;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\Juhe\Client;
use PHPUnit\Framework\TestCase;

/**
 * 聚合数据 聚合查询客户端测试
 */
class ClientTest extends TestCase
{
    private function makeClient(): Client
    {
        return ExpressApiClient::create('juhe', ['app_key' => 'k', 'app_secret' => 's']);
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
        $client->cancelOrder('123');
    }

    public function testRealQueryRequiresNetwork(): void
    {
        $this->markTestIncomplete('真实轨迹查询需聚合数据凭证，避免触网；结构 / 校验见其他用例');
        $this->makeClient()->queryTracking('SF1234567890123');
    }
}
