<?php

namespace Blaspsoft\Blasp\Laravel;

use Illuminate\Support\ServiceProvider;
use Blaspsoft\Blasp\Core\Dictionary;

class BlaspServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/blasp.php' => config_path('blasp.php'),
            ], 'blasp-config');

            $this->publishes([
                __DIR__ . '/../../config/languages' => config_path('languages'),
            ], 'blasp-languages');

            $this->publishes([
                __DIR__ . '/../../config/blasp.php' => config_path('blasp.php'),
                __DIR__ . '/../../config/languages' => config_path('languages'),
            ], 'blasp');

            $this->commands([
                Console\ClearCommand::class,
                Console\TestCommand::class,
                Console\LanguagesCommand::class,
            ]);
        }

        $this->registerValidationRule();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/blasp.php', 'blasp');

        $this->app->singleton('blasp', function ($app) {
            return new BlaspManager($app);
        });

        $this->app->alias('blasp', BlaspManager::class);
    }

    protected function registerValidationRule(): void
    {
        $this->app['validator']->extend('blasp_check', function ($attribute, $value, $parameters) {
            $language = $parameters[0] ?? config('blasp.language', config('blasp.default_language', 'english'));

            $manager = $this->app->make('blasp');

            $result = $manager->in($language)->check($value);

            return !$result->isOffensive();
        }, 'The :attribute contains profanity.');
    }
}
