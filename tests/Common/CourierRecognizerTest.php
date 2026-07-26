<?php

namespace Kode\ExpressApi\Tests\Common;

use Kode\ExpressApi\Common\CourierRecognizer;
use PHPUnit\Framework\TestCase;

/**
 * 承运商自动识别器测试
 *
 * 验证「仅凭运单号自动推断承运商」这一自动关联能力（离线、纯逻辑）。
 */
class CourierRecognizerTest extends TestCase
{
    protected function setUp(): void
    {
        // 每个用例前重置为内置规则，避免相互污染
        CourierRecognizer::reset();
    }

    public function testDetectSfByPrefix(): void
    {
        $this->assertSame('sf', CourierRecognizer::detect('SF1234567890123'));
    }

    public function testDetectJdByPrefix(): void
    {
        $this->assertSame('jd', CourierRecognizer::detect('JD0091234567890'));
        $this->assertSame('jd', CourierRecognizer::detect('JDX1234567890123'));
    }

    public function testDetectZtoByPrefix(): void
    {
        $this->assertSame('zto', CourierRecognizer::detect('73' . str_repeat('1', 12)));
        $this->assertSame('zto', CourierRecognizer::detect('ZTO888888888888'));
    }

    public function testDetectStoByPrefix(): void
    {
        $this->assertSame('sto', CourierRecognizer::detect('77' . str_repeat('0', 12)));
    }

    public function testDetectYundaByPrefix(): void
    {
        $this->assertSame('yunda', CourierRecognizer::detect('YD1234567890123'));
    }

    public function testDetectFourPxByPrefix(): void
    {
        $this->assertSame('fourpx', CourierRecognizer::detect('LP123456789012'));
        $this->assertSame('fourpx', CourierRecognizer::detect('4PX123456789'));
    }

    public function testDetectDhlByNumericLength(): void
    {
        $this->assertSame('dhl', CourierRecognizer::detect('1234567890'));
    }

    public function testDetectFedExByNumericLength(): void
    {
        // FedEx 单号常见 12–15 位数字（与 DHL 10–11 位区分）
        $this->assertSame('fedex', CourierRecognizer::detect('1234567890123'));
        $this->assertSame('fedex', CourierRecognizer::detect('123456789012345'));
    }

    public function testDetectUpsByPrefix(): void
    {
        // UPS 单号典型以 1Z 开头 + 16 位字母数字
        $this->assertSame('ups', CourierRecognizer::detect('1Z1234567890123456'));
    }

    public function testDetectJtByPrefix(): void
    {
        $this->assertSame('jt', CourierRecognizer::detect('JT1234567890123'));
    }

    public function testDetectYtoByPrefix(): void
    {
        $this->assertSame('yto', CourierRecognizer::detect('YT1234567890123'));
    }

    public function testDetectUspsByNumericLength(): void
    {
        // USPS 单号常见 16 位以上纯数字（以 9 开头，与 DHL 10–11 / FedEx 12–15 区分）
        $this->assertSame('usps', CourierRecognizer::detect('9123456789012345'));
    }

    public function testDetectPostnlByPrefix(): void
    {
        $this->assertSame('postnl', CourierRecognizer::detect('3S123456789012'));
    }

    public function testDetectRoyalMailByPattern(): void
    {
        $this->assertSame('royalmail', CourierRecognizer::detect('AB12345678901GB'));
    }

    public function testDetectBpostByPrefix(): void
    {
        $this->assertSame('bpost', CourierRecognizer::detect('3V1234567890'));
    }

    public function testDetectSingpostByPrefix(): void
    {
        $this->assertSame('singpost', CourierRecognizer::detect('RR1234567890'));
    }

    public function testDetectBestByPrefix(): void
    {
        $this->assertSame('best', CourierRecognizer::detect('BS1234567890'));
        $this->assertSame('best', CourierRecognizer::detect('BEST1234567890'));
    }

    public function testDetectDebangByPrefix(): void
    {
        $this->assertSame('debang', CourierRecognizer::detect('DE1234567890'));
    }

    public function testDetectEmsInternationalByPattern(): void
    {
        $this->assertSame('ems', CourierRecognizer::detect('EE123456789CN'));
    }

    public function testDetectReturnsNullWhenNoMatch(): void
    {
        $this->assertNull(CourierRecognizer::detect('some-random-string-xyz'));
    }

    public function testDetectEmptyReturnsNull(): void
    {
        $this->assertNull(CourierRecognizer::detect('   '));
    }

    public function testDetectBatch(): void
    {
        $result = CourierRecognizer::detectBatch(['SF1234567890123', 'JD0091234567890', 'nope']);
        $this->assertSame('sf', $result['SF1234567890123']);
        $this->assertSame('jd', $result['JD0091234567890']);
        $this->assertNull($result['nope']);
    }

    public function testRegisterPatternOverrides(): void
    {
        // 为新承运商注册规则
        CourierRecognizer::registerPattern('mycourier', '/^MY\d{6}$/i');
        $this->assertSame('mycourier', CourierRecognizer::detect('MY123456'));

        // 覆盖既有规则：原 sf 的 SF 前缀规则被替换
        CourierRecognizer::registerPattern('sf', '/^CUSTOM-\d+$/');
        $this->assertSame('sf', CourierRecognizer::detect('CUSTOM-123'));
        // 注意：SF 国际规则（/^SF/i）仍命中原 SF 单号，体现同族重叠
        $this->assertSame('sf_international', CourierRecognizer::detect('SF1234567890123'));
    }

    public function testResolverFallback(): void
    {
        // 规则未命中时，委托聚合解析器（权威回退）
        CourierRecognizer::setResolver(function (string $no) {
            if (str_starts_with($no, 'AGG-')) {
                return 'yanwen';
            }
            return null;
        });

        $this->assertNull(CourierRecognizer::detect('totally-unknown'));
        $this->assertSame('yanwen', CourierRecognizer::detect('AGG-987654'));
    }

    public function testAllPatternsReturnsArray(): void
    {
        $patterns = CourierRecognizer::allPatterns();
        $this->assertIsArray($patterns);
        $this->assertArrayHasKey('sf', $patterns);
        $this->assertArrayHasKey('jd', $patterns);
    }
}
