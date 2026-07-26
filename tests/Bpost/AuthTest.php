<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Bpost\Auth;
use Kode\ExpressApi\Bpost\Config;
use PHPUnit\Framework\TestCase;

class BpostAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('bpost', $auth->getProviderName());
    }
}
