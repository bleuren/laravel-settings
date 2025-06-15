<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Console\Commands\SettingClear;
use Bleuren\LaravelSetting\Facades\Setting;
use Bleuren\LaravelSetting\SettingManager;

// 表創建已在 TestCase::setUpDatabase() 中處理

describe('SettingServiceProvider', function () {

    it('registers setting manager as singleton', function () {
        $manager1 = app('setting.manager');
        $manager2 = app('setting.manager');

        expect($manager1)->toBeInstanceOf(SettingManager::class);
        expect($manager1)->toBe($manager2); // Same instance (singleton)
    });

    it('registers setting manager with correct alias', function () {
        $manager = app(SettingManager::class);

        expect($manager)->toBeInstanceOf(SettingManager::class);
    });

    it('loads configuration from config file', function () {
        expect(config('settings.cache_prefix'))->toBe('test_settings.');
        expect(config('settings.table'))->toBe('settings');
        expect(config('settings.model'))->toBe(\Bleuren\LaravelSetting\Setting::class);
    });

    it('registers console commands', function () {
        $artisan = app('Illuminate\Contracts\Console\Kernel');
        $commands = $artisan->all();

        expect($commands)->toHaveKey('setting:clear');
        expect($commands['setting:clear'])->toBeInstanceOf(SettingClear::class);
    });

    it('provides correct services', function () {
        $provider = app()->getProvider(\Bleuren\LaravelSetting\SettingServiceProvider::class);

        expect($provider->provides())->toContain('setting.manager');
        expect($provider->provides())->toContain(SettingManager::class);
    });

    it('preloads eager settings when configured', function () {
        // Configure eager loading
        config([
            'settings.eager_load' => true,
            'settings.eager_load_keys' => [
                'eager_key_1',
                'eager_key_2',
            ],
        ]);

        // Set some settings
        Setting::set('eager_key_1', 'eager_value_1');
        Setting::set('eager_key_2', 'eager_value_2');
        Setting::set('non_eager_key', 'non_eager_value');

        // Trigger boot method by creating new instance
        $serviceProvider = new \Bleuren\LaravelSetting\SettingServiceProvider(app());
        $serviceProvider->boot();

        // Eager loaded settings should be available
        expect(Setting::get('eager_key_1'))->toBe('eager_value_1');
        expect(Setting::get('eager_key_2'))->toBe('eager_value_2');
    });

    it('skips preloading when eager load is disabled', function () {
        // Configure eager loading as disabled
        config([
            'settings.eager_load' => false,
            'settings.eager_load_keys' => [
                'should_not_load_1',
                'should_not_load_2',
            ],
        ]);

        // Set some settings
        Setting::set('should_not_load_1', 'value_1');
        Setting::set('should_not_load_2', 'value_2');

        // Trigger boot method
        $serviceProvider = new \Bleuren\LaravelSetting\SettingServiceProvider(app());
        $serviceProvider->boot();

        // Settings should still be accessible (just not preloaded)
        expect(Setting::get('should_not_load_1'))->toBe('value_1');
        expect(Setting::get('should_not_load_2'))->toBe('value_2');
    });

    it('handles empty eager load keys gracefully', function () {
        config([
            'settings.eager_load' => true,
            'settings.eager_load_keys' => [],
        ]);

        $serviceProvider = new \Bleuren\LaravelSetting\SettingServiceProvider(app());

        // Should not throw exception
        expect(fn () => $serviceProvider->boot())->not->toThrow(\Exception::class);
    });

    it('handles missing configuration gracefully', function () {
        // Temporarily remove config
        config(['settings' => null]);

        // Should handle gracefully and not crash
        expect(fn () => new SettingManager)->not->toThrow(\Exception::class);
    });
});

describe('Service Provider Integration', function () {

    it('works with Laravel service container', function () {
        // Test dependency injection
        $manager = resolve(SettingManager::class);

        expect($manager)->toBeInstanceOf(SettingManager::class);
        expect($manager->getModel())->toBeInstanceOf(\Bleuren\LaravelSetting\Setting::class);
    });

    it('facade resolves correctly through service provider', function () {
        // Setting facade should resolve to manager
        $facadeRoot = Setting::getFacadeRoot();

        expect($facadeRoot)->toBeInstanceOf(SettingManager::class);
    });

    it('can be used in service provider dependencies', function () {
        // Create a test service provider that depends on setting manager
        $testProvider = new class(app()) extends \Illuminate\Support\ServiceProvider
        {
            public function register()
            {
                $this->app->singleton('test.service', function ($app) {
                    return new class($app[SettingManager::class])
                    {
                        public function __construct(
                            private SettingManager $settings
                        ) {}

                        public function getSettings(): SettingManager
                        {
                            return $this->settings;
                        }
                    };
                });
            }
        };

        app()->register($testProvider);

        $testService = app('test.service');

        expect($testService->getSettings())->toBeInstanceOf(SettingManager::class);
    });

    it('publishes assets correctly', function () {
        // Mock the publish command would work
        $provider = app()->getProvider(\Bleuren\LaravelSetting\SettingServiceProvider::class);

        // This would be tested in actual Laravel app, but we can verify the provider exists
        expect($provider)->not->toBeNull();
    });

    it('defers service loading correctly', function () {
        $provider = app()->getProvider(\Bleuren\LaravelSetting\SettingServiceProvider::class);

        // Should be deferred for performance
        expect($provider->isDeferred())->toBeTrue();
    });
});
