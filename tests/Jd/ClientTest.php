<?php

namespace Kode\ExpressApi\Tests\Jd;

use Kode\ExpressApi\Common\Exception\ExpressApiException;
use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\Jd\Client;
use PHPUnit\Framework\TestCase;

/**
 * 京东快递 / 京东物流 客户端测试
 */
class ClientTest extends TestCase
{
    private function makeClient(): Client
    {
        return ExpressApiClient::create('jd', ['app_key' => 'k', 'app_secret' => 's']);
    }

    public function testQueryTrackingThrowsWhenNumberEmpty(): void
    {
        $this->expectException(ExpressApiException::class);
        $this->makeClient()->queryTracking('');
    }

    public function testQueryOrderThrowsWhenOrderIdEmpty(): void
    {
        $this->expectException(ExpressApiException::class);
        $this->makeClient()->queryOrder('');
    }

    public function testSendShipmentValidatesRequiredFields(): void
    {
        $this->expectException(ExpressApiException::class);
        // 缺少 recipient / items 等必填项，校验应在触网前抛出
        $this->makeClient()->sendShipment(['order_no' => 'T1', 'sender' => []]);
    }

    public function testCancelOrderThrowsWhenOrderIdEmpty(): void
    {
        $this->expectException(ExpressApiException::class);
        $this->makeClient()->cancelOrder('');
    }

    public function testPrintLabelThrowsWhenOrderIdEmpty(): void
    {
        $this->expectException(ExpressApiException::class);
        $this->makeClient()->printLabel('');
    }

    public function testRealCallRequiresNetwork(): void
    {
        $this->markTestIncomplete('真实轨迹查询需京东凭证，避免触网；结构 / 校验见其他用例');
        $this->makeClient()->queryTracking('SF1234567890123');
    }
}
