<?php

namespace Kode\ExpressApi\Tests;

use Kode\ExpressApi\Postnl\Auth;
use Kode\ExpressApi\Postnl\Config;
use PHPUnit\Framework\TestCase;

class PostnlAuthTest extends TestCase
{
    public function testProviderName(): void
    {
        $auth = new Auth(new Config(['app_key' => 'k', 'app_secret' => 's']));
        $this->assertSame('PostNL', $auth->getProviderName());
    }
}
