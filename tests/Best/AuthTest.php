<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Best\Auth;
use Kode\ExpressApi\Best\Config;
use PHPUnit\Framework\TestCase;

class BestAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('百世快递', $auth->getProviderName());
    }
}
