<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Royalmail\Config;
use PHPUnit\Framework\TestCase;

class RoyalmailConfigTest extends TestCase
{
    public function testProductionBaseUrl(): void
    {
        $this->assertSame('https://api.royalmail.com', (new Config())->getBaseUrl());
    }

    public function testSandboxBaseUrl(): void
    {
        $this->assertSame(
            'https://api-qa.royalmail.com',
            (new Config(['sandbox' => true]))->getBaseUrl()
        );
    }

    public function testVersionIsEmpty(): void
    {
        $this->assertSame('', (new Config())->getVersion());
    }
}
