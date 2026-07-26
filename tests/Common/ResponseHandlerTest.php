<?php

namespace Kode\ExpressApi\Tests\Common;

use Kode\ExpressApi\Common\ResponseHandler;
use Kode\ExpressApi\Common\Exception\ExpressApiException;
use PHPUnit\Framework\TestCase;

/**
 * 响应处理器策略测试
 *
 * 覆盖：EMS / 顺丰 预置策略、默认保守策略、自定义策略注册（registerPolicy）。
 */
class ResponseHandlerTest extends TestCase
{
    public function testEmsSuccessUnwrapsData(): void
    {
        $response = ['success' => true, 'data' => ['order_no' => 'E1']];
        $this->assertEquals(['order_no' => 'E1'], ResponseHandler::handle($response, 'ems'));
    }

    public function testEmsFailureThrows(): void
    {
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['success' => false, 'error' => ['message' => '拒收', 'code' => 9]], 'ems');
    }

    public function testSfSuccessUnwrapsData(): void
    {
        $response = ['status' => 'success', 'data' => ['waybill' => 'SF1']];
        $this->assertEquals(['waybill' => 'SF1'], ResponseHandler::handle($response, 'sf'));
    }

    public function testSfFailureByErrorFieldThrows(): void
    {
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['error' => ['message' => '鉴权失败']], 'sf');
    }

    public function testSfFailureByStatusThrows(): void
    {
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['status' => 'error', 'message' => '业务异常'], 'sf');
    }

    public function testDefaultPolicyIgnoresSuccessCode(): void
    {
        // code=200 / code=0 视为成功，原样返回（不擅自解包）
        $ok = ['code' => 200, 'message' => 'ok', 'payload' => ['a' => 1]];
        $this->assertSame($ok, ResponseHandler::handle($ok, 'yunda'));

        $okZero = ['code' => 0, 'data' => ['x' => 1]];
        $this->assertSame($okZero, ResponseHandler::handle($okZero, 'zto'));
    }

    public function testDefaultPolicyThrowsOnExplicitError(): void
    {
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['error' => 'boom'], 'sto');
    }

    public function testDefaultPolicyThrowsOnSuccessFalse(): void
    {
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['success' => false, 'msg' => 'fail'], 'cainiao');
    }

    public function testDefaultPolicyThrowsOnHttpStyleErrorCode(): void
    {
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['code' => 500, 'message' => 'server error'], 'unknown_courier');
    }

    public function testCustomPolicyCanBeRegistered(): void
    {
        ResponseHandler::registerPolicy(
            'demo',
            static fn(array $r): bool => ($r['result'] ?? '') === 'FAIL',
            static fn(array $r): array => $r['body'] ?? $r
        );

        // 成功：解包 body
        $this->assertEquals(['k' => 'v'], ResponseHandler::handle(['result' => 'OK', 'body' => ['k' => 'v']], 'demo'));
        // 失败：抛错
        $this->expectException(ExpressApiException::class);
        ResponseHandler::handle(['result' => 'FAIL', 'body' => []], 'demo');
    }

    public function testGetPolicyReturnsRegistered(): void
    {
        ResponseHandler::registerPolicy('policy_x', static fn(): bool => false, static fn($r) => $r);
        $policy = ResponseHandler::getPolicy('policy_x');
        $this->assertIsArray($policy);
        $this->assertArrayHasKey('isError', $policy);
        $this->assertArrayHasKey('unwrap', $policy);
    }

    public function testProviderIsCaseInsensitive(): void
    {
        $response = ['success' => true, 'data' => ['id' => 1]];
        $this->assertEquals(['id' => 1], ResponseHandler::handle($response, 'EMS'));
    }
}
