<?php

namespace Blaspsoft\Blasp;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Blaspsoft\Blasp\Core\Contracts\DriverInterface;
use Blaspsoft\Blasp\Drivers\RegexDriver;
use Blaspsoft\Blasp\Drivers\PatternDriver;
use Blaspsoft\Blasp\Drivers\PhoneticDriver;
use InvalidArgumentException;

class BlaspManager
{
    protected Application $app;
    protected array $drivers = [];
    protected array $customCreators = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function driver(?string $driver = null): PendingCheck
    {
        return $this->newPendingCheck()->driver($driver ?? $this->getDefaultDriver());
    }

    public function resolveDriver(string $name): DriverInterface
    {
        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    protected function createDriver(string $name): DriverInterface
    {
        if (isset($this->customCreators[$name])) {
            return ($this->customCreators[$name])($this->app);
        }

        $method = 'create' . ucfirst($name) . 'Driver';
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [{$name}] not supported.");
    }

    public function createRegexDriver(): DriverInterface
    {
        return new RegexDriver();
    }

    public function createPatternDriver(): DriverInterface
    {
        return new PatternDriver();
    }

    public function createPhoneticDriver(): DriverInterface
    {
        $config = $this->app['config']->get('blasp.drivers.phonetic', []);

        return new PhoneticDriver(
            phonemes: $config['phonemes'] ?? 4,
            minWordLength: $config['min_word_length'] ?? 3,
            maxDistanceRatio: $config['max_distance_ratio'] ?? 0.6,
            phoneticFalsePositives: $config['false_positives'] ?? [],
            supportedLanguages: $config['supported_languages'] ?? ['english'],
        );
    }

    public function extend(string $driver, Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;
        return $this;
    }

    public function getDefaultDriver(): string
    {
        return $this->app['config']->get('blasp.default', 'regex');
    }

    public function newPendingCheck(): PendingCheck
    {
        return new PendingCheck($this);
    }

    // --- Shortcut methods that create PendingCheck ---

    public function check(?string $text): \Blaspsoft\Blasp\Core\Result
    {
        return $this->newPendingCheck()->check($text);
    }

    public function checkMany(array $texts): array
    {
        return $this->newPendingCheck()->checkMany($texts);
    }

    public function __call(string $method, array $parameters): mixed
    {
        return $this->newPendingCheck()->$method(...$parameters);
    }

    public function getApp(): Application
    {
        return $this->app;
    }
}
