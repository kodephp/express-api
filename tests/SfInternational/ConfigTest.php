<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\SfInternational\Config;
use PHPUnit\Framework\TestCase;

class SfInternationalConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://openapi-portal.sf.global/api', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://openapi-portal.sf.global/sandbox/api',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }

    public function testCustomerCode(): void
    {
        $config = new Config(['customer_code' => 'CUST1']);
        $this->assertSame('CUST1', $config->getCustomerCode());
    }
}
