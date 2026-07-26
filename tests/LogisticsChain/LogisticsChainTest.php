<?php

namespace Kode\ExpressApi\Tests\LogisticsChain;

use Kode\ExpressApi\ExpressApiClient;
use Kode\ExpressApi\LogisticsChain\LogisticsChain;
use PHPUnit\Framework\TestCase;

/**
 * 物流链自动编排测试
 *
 * 验证「无需手动逐段指定承运商」：给定发货意图或运单号，
 * 编排器自动选商并拼装整条链路。track() 因涉及真实网络，
 * 以 Incomplete 标注，避免触网；结构验证由 compose / fromTracking 用例覆盖。
 */
class LogisticsChainTest extends TestCase
{
    private function fullConfigs(): array
    {
        return [
            'sf'               => [],
            'debang'           => [],
            'dhl'              => [],
            'ems_international' => [],
            'jd'               => [],
        ];
    }

    public function testComposeInternationalAutoSelectsCarriers(): void
    {
        $chain = ExpressApiClient::buildChain(
            ['origin' => 'CN', 'dest' => 'US', 'weight' => 5, 'mode' => 'air'],
            $this->fullConfigs()
        );

        $legs = $chain->toArray()['legs'];
        $this->assertCount(5, $legs);

        $byLeg = array_column($legs, 'courier', 'leg');
        $this->assertSame('sf', $byLeg[LogisticsChain::LEG_PICKUP]);
        $this->assertSame('debang', $byLeg[LogisticsChain::LEG_LINEHAUL]);
        $this->assertSame('dhl', $byLeg[LogisticsChain::LEG_CROSSBORDER]);
        $this->assertSame('ems_international', $byLeg[LogisticsChain::LEG_CUSTOMS]);
        $this->assertSame('jd', $byLeg[LogisticsChain::LEG_LASTMILE]);

        foreach ($legs as $leg) {
            $this->assertTrue($leg['ready'], "环节 {$leg['leg']} 应已就绪");
        }
    }

    public function testComposeDomesticOnlyTwoLegs(): void
    {
        $chain = ExpressApiClient::buildChain(
            ['origin' => 'CN', 'dest' => 'CN', 'weight' => 3],
            $this->fullConfigs()
        );

        $legs = $chain->toArray()['legs'];
        $this->assertCount(2, $legs);
        $this->assertSame(LogisticsChain::LEG_PICKUP, $legs[0]['leg']);
        $this->assertSame(LogisticsChain::LEG_LINEHAUL, $legs[1]['leg']);
    }

    public function testComposePreferOverride(): void
    {
        $configs = $this->fullConfigs();
        $configs['fourpx'] = [];

        $chain = ExpressApiClient::buildChain(
            ['origin' => 'CN', 'dest' => 'US', 'mode' => 'air'],
            $configs,
            ['crossborder' => 'fourpx']
        );

        $legs = $chain->toArray()['legs'];
        $byLeg = array_column($legs, 'courier', 'leg');
        $this->assertSame('fourpx', $byLeg[LogisticsChain::LEG_CROSSBORDER]);
    }

    public function testComposeFallbackWhenDefaultUnconfigured(): void
    {
        // 仅签约京东：揽收 / 末端可回退到 jd，干线/跨境/清关未配置 → 标记 unavailable
        $chain = ExpressApiClient::buildChain(
            ['origin' => 'CN', 'dest' => 'US', 'mode' => 'air'],
            ['jd' => []]
        );

        $legs = $chain->toArray()['legs'];
        $byLeg = array_column($legs, 'courier', 'leg');
        $byReady = array_column($legs, 'ready', 'leg');

        $this->assertSame('jd', $byLeg[LogisticsChain::LEG_PICKUP]);
        $this->assertTrue($byReady[LogisticsChain::LEG_PICKUP]);

        $this->assertNull($byLeg[LogisticsChain::LEG_LINEHAUL]);
        $this->assertFalse($byReady[LogisticsChain::LEG_LINEHAUL]);

        $this->assertSame('jd', $byLeg[LogisticsChain::LEG_LASTMILE]);
        $this->assertTrue($byReady[LogisticsChain::LEG_LASTMILE]);
    }

    public function testFromTrackingDetectesCourierAndSuggestsChain(): void
    {
        $chain = ExpressApiClient::chainFromTracking('SF1234567890123', ['sf' => []]);

        $info = $chain->toArray();
        $this->assertSame('sf', $info['detected_courier']);

        // 推断的完整链路模板应包含 5 个环节
        $this->assertCount(5, $info['suggested_chain']);

        // 已识别承运商落到末端派送环节且就绪
        $legs = $info['legs'];
        $this->assertSame('sf', $legs[0]['courier']);
        $this->assertTrue($legs[0]['ready']);
    }

    public function testTrackQueriesWholeChain(): void
    {
        $this->markTestIncomplete(
            'track() 需真实承运商凭证，避免触网；结构验证见 compose / fromTracking 用例'
        );
    }
}
