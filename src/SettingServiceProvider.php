<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting;

use Bleuren\LaravelSetting\Console\Commands\SettingClear;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class SettingServiceProvider extends ServiceProvider
{
    /**
     * 啟動套件服務
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            // 發布遷移文件
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'laravel-settings-migrations');

            // 發布配置文件
            $this->publishes([
                __DIR__.'/../config/settings.php' => config_path('settings.php'),
            ], 'laravel-settings-config');

            // 註冊命令
            $this->commands([
                SettingClear::class,
            ]);
        }
    }

    /**
     * 註冊套件服務
     */
    public function register(): void
    {
        // 註冊單例
        $this->app->singleton('setting', function (Application $app) {
            return new SettingManager;
        });

        // 合併配置文件
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        // 在應用啟動時預載入常用設定
        $this->app->booted(function () {
            if (config('settings.eager_load', false)) {
                $this->preloadSettings();
            }
        });
    }

    /**
     * 獲取由此服務提供者提供的服務
     */
    public function provides(): array
    {
        return ['setting'];
    }

    /**
     * 預載入常用設定
     */
    protected function preloadSettings(): void
    {
        $keys = config('settings.eager_load_keys', []);
        if (empty($keys)) {
            return;
        }

        try {
            $settingManager = app('setting');
            foreach ($keys as $key) {
                $settingManager->get($key);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
