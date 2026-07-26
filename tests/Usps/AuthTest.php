<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Usps\Auth;
use Kode\ExpressApi\Usps\Config;
use PHPUnit\Framework\TestCase;

class UspsAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('USPS', $auth->getProviderName());
    }
}
