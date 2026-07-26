<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Bpost\Config;
use PHPUnit\Framework\TestCase;

class BpostConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.bpost.be', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        // bpost 沙箱通过 X-ApiKey 区分，host 与生产相同
        $this->assertSame(
            'https://api.bpost.be',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }
}
