<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting;

use Bleuren\LaravelSetting\Console\Commands\SettingClear;
use Bleuren\LaravelSetting\Contracts\SettingRepository;
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

        // 在 HTTP 請求中進行預載入（避免在 console 命令中執行）
        if (! $this->app->runningInConsole() && config('settings.eager_load', false)) {
            $this->app->booted(function () {
                $this->preloadSettings();
            });
        }
    }

    /**
     * 註冊套件服務
     */
    public function register(): void
    {
        // 合併配置文件
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        // 註冊 SettingManager 單例
        $this->app->singleton('setting.manager', function (Application $app) {
            return new SettingManager;
        });

        // 綁定 Contract 到實現
        $this->app->bind(SettingRepository::class, function (Application $app) {
            return $app['setting.manager'];
        });

        // 註冊別名以保持向後兼容
        $this->app->alias('setting.manager', 'setting');
        $this->app->alias('setting.manager', SettingManager::class);
        $this->app->alias('setting.manager', SettingRepository::class);
    }

    /**
     * 獲取由此服務提供者提供的服務
     */
    public function provides(): array
    {
        return [
            'setting.manager',
            'setting',
            SettingManager::class,
            SettingRepository::class,
        ];
    }

    /**
     * 指示服務提供者是否延遲載入
     */
    public function isDeferred(): bool
    {
        return true;
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
            // 確保資料庫連接可用
            if (! $this->app->bound('db') || ! $this->isDatabaseReady()) {
                return;
            }

            $settingManager = app('setting.manager');
            foreach ($keys as $key) {
                $settingManager->get($key);
            }
        } catch (\Throwable $e) {
            // 靜默處理預載入錯誤，不影響應用啟動
            if (config('app.debug', false)) {
                report($e);
            }
        }
    }

    /**
     * 檢查資料庫是否準備就緒
     */
    protected function isDatabaseReady(): bool
    {
        try {
            $connection = config('settings.database_connection') ?: config('database.default');
            $tableName = config('settings.table', 'settings');

            return $this->app['db']->connection($connection)
                ->getSchemaBuilder()
                ->hasTable($tableName);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
