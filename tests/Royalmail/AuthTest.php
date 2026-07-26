<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Royalmail\Auth;
use Kode\ExpressApi\Royalmail\Config;
use PHPUnit\Framework\TestCase;

class RoyalmailAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('RoyalMail', $auth->getProviderName());
    }
}
