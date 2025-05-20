<?php

declare(strict_types=1);

namespace Bleuren\Setting;

use Bleuren\Setting\Console\Commands\SettingClear;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'laravel-settings');

            $this->commands([
                SettingClear::class,
            ]);
        }
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->app->singleton('setting', function ($app) {
            return new Setting;
        });

        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');
    }
}
