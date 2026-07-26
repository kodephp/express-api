<?php

namespace Kode\ExpressApi\Tests\Common;

use Kode\ExpressApi\Common\Resolver\AggregateResolver;
use Kode\ExpressApi\Common\Resolver\ResolverSourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * 聚合解析器测试（离线，使用测试桩模拟聚合查询源）
 */
class AggregateResolverTest extends TestCase
{
    /**
     * 构造一个返回固定外部代码的测试桩解析源
     */
    private function stub(string $externalCode): ResolverSourceInterface
    {
        return new class($externalCode) implements ResolverSourceInterface {
            public function __construct(private string $code) {}

            public function recognizeTracking(string $trackingNumber): array
            {
                return ['courier' => $this->code, 'raw' => []];
            }
        };
    }

    public function testMapsExternalCodeToInternalCourier(): void
    {
        // 17TRACK / 快递100 返回 "shunfeng" → 内部 'sf'
        $resolver = new AggregateResolver();
        $resolver->add($this->stub('shunfeng'));

        $this->assertSame('sf', $resolver->resolve('ANY-NUMBER'));
    }

    public function testUnknownExternalCodeResolvesToNull(): void
    {
        // 真实未知的外部代码（SDK 未收录）→ 整体返回 null
        $resolver = new AggregateResolver();
        $resolver->add($this->stub('unknowncarrierxyz'));

        $this->assertNull($resolver->resolve('ANY-NUMBER'));
    }

    public function testChainsSourcesFirstSupportedWins(): void
    {
        // 第一家返回已接入的 "ups" → 直接采用，不再询问第二家
        $resolver = new AggregateResolver();
        $resolver->add($this->stub('ups'));
        $resolver->add($this->stub('zhongtong'));

        $this->assertSame('ups', $resolver->resolve('ANY-NUMBER'));
        $this->assertSame(2, $resolver->count());
    }

    public function testNewlySupportedExternalCodesResolveToInternal(): void
    {
        // 所有历史预留的「已知但未接入」渠道已在 v2.6.0 接入，别名映射为对应内部代码
        foreach ([
            'fedex' => 'fedex', 'ups' => 'ups', 'yuantong' => 'yto',
            'usps' => 'usps', 'postnl' => 'postnl', 'royalmail' => 'royalmail',
            'bpost' => 'bpost', 'singpost' => 'singpost', 'htky' => 'best',
        ] as $ext => $internal) {
            $resolver = new AggregateResolver();
            $resolver->add($this->stub($ext));
            $this->assertSame($internal, $resolver->resolve('ANY-NUMBER'));
        }
    }

    public function testSourceFailureDoesNotBreakChain(): void
    {
        $failing = new class implements ResolverSourceInterface {
            public function recognizeTracking(string $trackingNumber): array
            {
                throw new \RuntimeException('network error');
            }
        };

        $resolver = new AggregateResolver();
        $resolver->add($failing);
        $resolver->add($this->stub('ems'));

        $this->assertSame('ems', $resolver->resolve('ANY-NUMBER'));
    }

    public function testIsInvokableAsCallable(): void
    {
        $resolver = new AggregateResolver();
        $resolver->add($this->stub('jd'));

        // __invoke 使解析器可直接作为 callable 传给 CourierRecognizer::detect / setResolver
        $this->assertSame('jd', ($resolver)('ANY-NUMBER'));
    }

    public function testCaseInsensitiveExternalCode(): void
    {
        $resolver = new AggregateResolver();
        $resolver->add($this->stub('SHUNFENG'));

        $this->assertSame('sf', $resolver->resolve('ANY-NUMBER'));
    }

    public function testRegisterAliasExtendsMapping(): void
    {
        AggregateResolver::registerAlias('mycarrier', 'sf');
        $resolver = new AggregateResolver();
        $resolver->add($this->stub('mycarrier'));

        $this->assertSame('sf', $resolver->resolve('ANY-NUMBER'));

        // 还原，避免污染其他测试
        AggregateResolver::registerAlias('mycarrier', null);
    }
}
