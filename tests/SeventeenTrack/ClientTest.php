<?php

namespace Kode\ExpressApi\Tests\SeventeenTrack;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\SeventeenTrack\Client;
use PHPUnit\Framework\TestCase;

/**
 * 17TRACK 聚合查询客户端测试
 */
class ClientTest extends TestCase
{
    private function makeClient(): Client
    {
        return ExpressApiClient::create('seventeentrack', ['app_secret' => 'TOKEN']);
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
        $client->sendShipment([]);
    }

    public function testImplementsResolverSource(): void
    {
        // 可作为 AggregateResolver 的解析源参与聚合识别
        $client = $this->makeClient();
        $this->assertInstanceOf(
            \Kode\ExpressApi\Common\Resolver\ResolverSourceInterface::class,
            $client
        );
    }

    public function testRealQueryRequiresNetwork(): void
    {
        $this->markTestIncomplete('真实轨迹 / 识别需 17TRACK 凭证，避免触网；结构 / 校验见其他用例');
        $this->makeClient()->queryTracking('UNKNOWN-INTL-999');
    }
}
