<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Laravel\Middleware\CheckProfanity;

class MiddlewareAliasTest extends TestCase
{
    public function test_blasp_alias_resolves_to_check_profanity_middleware()
    {
        $router = $this->app['router'];

        $aliases = $router->getMiddleware();

        $this->assertArrayHasKey('blasp', $aliases);
        $this->assertSame(CheckProfanity::class, $aliases['blasp']);
    }
}
