<?php

declare(strict_types=1);

namespace Tests;

use Bleuren\LaravelSetting\SettingServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // 手動創建測試表，因為我們使用記憶體資料庫
        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SettingServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Setting' => \Bleuren\LaravelSetting\Facades\Setting::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // 設定測試資料庫
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // 設定套件配置
        $app['config']->set('settings.cache_prefix', 'test_settings.');
        $app['config']->set('settings.table', 'settings');
        $app['config']->set('settings.model', \Bleuren\LaravelSetting\Setting::class);
        $app['config']->set('settings.eager_load', false);
    }

    protected function setUpDatabase(): void
    {
        $schema = $this->app['db']->connection()->getSchemaBuilder();

        // 創建 settings 表（如果不存在）
        if (! $schema->hasTable('settings')) {
            $schema->create('settings', function ($table) {
                $table->id();
                $table->string('key', 191)->unique()->index();
                $table->string('description')->nullable();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        // 創建 custom_settings 表（如果不存在）
        if (! $schema->hasTable('custom_settings')) {
            $schema->create('custom_settings', function ($table) {
                $table->id();
                $table->string('key', 191)->index();
                $table->text('value')->nullable();
                $table->string('description')->nullable();
                $table->string('category')->default('general')->index();
                $table->boolean('is_public')->default(false);
                $table->timestamps();
                $table->unique(['key', 'category']);
            });
        }
    }
}
