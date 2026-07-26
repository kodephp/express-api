<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Singpost\Auth;
use Kode\ExpressApi\Singpost\Config;
use PHPUnit\Framework\TestCase;

class SingpostAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('SingPost', $auth->getProviderName());
    }
}
