<?php

namespace Blaspsoft\Blasp\Tests;

use Blaspsoft\Blasp\Laravel\BlaspManager;
use Blaspsoft\Blasp\Core\Contracts\DriverInterface;
use Blaspsoft\Blasp\Core\Dictionary;
use Blaspsoft\Blasp\Core\Contracts\MaskStrategyInterface;
use Blaspsoft\Blasp\Core\Result;
use Blaspsoft\Blasp\Drivers\RegexDriver;
use Blaspsoft\Blasp\Drivers\PatternDriver;
use InvalidArgumentException;

class DetectionStrategyRegistryTest extends TestCase
{
    private BlaspManager $manager;

    public function setUp(): void
    {
        parent::setUp();
        $this->manager = app('blasp');
    }

    public function test_default_driver_is_regex()
    {
        $this->assertEquals('regex', $this->manager->getDefaultDriver());
    }

    public function test_resolve_regex_driver()
    {
        $driver = $this->manager->resolveDriver('regex');
        $this->assertInstanceOf(RegexDriver::class, $driver);
    }

    public function test_resolve_pattern_driver()
    {
        $driver = $this->manager->resolveDriver('pattern');
        $this->assertInstanceOf(PatternDriver::class, $driver);
    }

    public function test_resolve_unknown_driver_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->manager->resolveDriver('unknown');
    }

    public function test_extend_registers_custom_driver()
    {
        $this->manager->extend('custom', function ($app) {
            return new class implements DriverInterface {
                public function detect(string $text, Dictionary $dictionary, MaskStrategyInterface $mask, array $options = []): Result
                {
                    return new Result($text, $text, [], 0);
                }
            };
        });

        $driver = $this->manager->resolveDriver('custom');
        $this->assertInstanceOf(DriverInterface::class, $driver);
    }

    public function test_manager_check_returns_result()
    {
        $result = $this->manager->check('fuck this');
        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isOffensive());
    }

    public function test_manager_creates_pending_check()
    {
        $pending = $this->manager->newPendingCheck();
        $this->assertInstanceOf(\Blaspsoft\Blasp\Laravel\PendingCheck::class, $pending);
    }

    public function test_driver_method_returns_pending_check()
    {
        $pending = $this->manager->driver('regex');
        $this->assertInstanceOf(\Blaspsoft\Blasp\Laravel\PendingCheck::class, $pending);
    }
}
