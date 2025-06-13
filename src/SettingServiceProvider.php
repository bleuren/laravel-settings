<?php

declare(strict_types=1);

namespace Bleuren\Setting;

use Bleuren\Setting\Console\Commands\SettingClear;
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
            return new Setting;
        });

        // 合併配置文件
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        // 延遲加載以提高性能
        $this->app->extend('setting', function (Setting $setting, Application $app) {
            // 在應用啟動時預載入常用設定
            if (config('settings.eager_load', false)) {
                if ($app->booted) {
                    $app->booting(function () {
                        $this->preloadSettings();
                    });
                }
            }

            return $setting;
        });
    }

    /**
     * 獲取由此服務提供者提供的服務。
     *
     * @return array<string>
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
        if (config('settings.eager_load_keys', [])) {
            $keys = config('settings.eager_load_keys', []);
            foreach ($keys as $key) {
                Setting::get($key);
            }
        }
    }
}
