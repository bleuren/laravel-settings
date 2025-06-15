<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Facades\Setting;

// 表創建已在 TestCase::setUpDatabase() 中處理

describe('Setting Facade', function () {

    it('can get and set settings via facade', function () {
        Setting::set('facade_test', 'facade_value', 'Facade test description');

        $value = Setting::get('facade_test');

        expect($value)->toBe('facade_value');
    });

    it('returns default value when setting does not exist', function () {
        $value = Setting::get('non_existent_facade', 'default_facade');

        expect($value)->toBe('default_facade');
    });

    it('can check if setting exists via facade', function () {
        expect(Setting::has('facade_exists_test'))->toBeFalse();

        Setting::set('facade_exists_test', 'value');

        expect(Setting::has('facade_exists_test'))->toBeTrue();
    });

    it('can set many settings via facade', function () {
        $settings = [
            'facade_batch1' => 'value1',
            'facade_batch2' => 'value2',
            'facade_batch3' => 'value3',
        ];

        $result = Setting::setMany($settings, 'Facade batch test');

        expect($result)->toHaveCount(3);
        expect(Setting::get('facade_batch1'))->toBe('value1');
        expect(Setting::get('facade_batch2'))->toBe('value2');
        expect(Setting::get('facade_batch3'))->toBe('value3');
    });

    it('can remove settings via facade', function () {
        Setting::set('facade_remove_test', 'value');
        expect(Setting::has('facade_remove_test'))->toBeTrue();

        $result = Setting::remove('facade_remove_test');

        expect($result)->toBeTrue();
        expect(Setting::has('facade_remove_test'))->toBeFalse();
    });

    it('can get all settings via facade', function () {
        Setting::set('facade_all1', 'value1');
        Setting::set('facade_all2', 'value2');
        Setting::set('facade_all3', 'value3');

        $allSettings = Setting::all();

        expect($allSettings)->toHaveCount(3);
    });

    it('can search settings via facade', function () {
        Setting::set('facade_search_app_name', 'App Name');
        Setting::set('facade_search_app_version', '1.0.0');
        Setting::set('facade_search_user_theme', 'dark');

        $searchResults = Setting::search('facade_search_app_%');

        expect($searchResults)->toHaveCount(2);
    });

    it('can clear memory cache via facade', function () {
        Setting::set('facade_cache_test', 'cached_value');

        // Should not throw exception
        expect(fn () => Setting::clearMemoryCache())->not->toThrow(\Exception::class);
    });

    it('can get cache key via facade', function () {
        $cacheKey = Setting::cacheKey('facade_cache_key_test');

        expect($cacheKey)->toContain('test_settings.Setting.facade_cache_key_test');
    });

    it('works with different model configurations', function () {
        // 清理環境
        \Illuminate\Support\Facades\Cache::flush();
        Setting::clearMemoryCache();

        // 測試 1: 驗證預設模型配置
        config(['settings.model' => \Bleuren\LaravelSetting\Setting::class]);
        app()->forgetInstance('setting.manager');
        Setting::clearResolvedInstances(); // 清除 Facade 緩存

        $manager1 = app('setting.manager');
        expect($manager1->getModel())->toBeInstanceOf(\Bleuren\LaravelSetting\Setting::class);

        // 測試 2: 驗證自定義模型配置
        config(['settings.model' => \Tests\Fixtures\CustomSetting::class]);
        app()->forgetInstance('setting.manager');
        Setting::clearResolvedInstances(); // 清除 Facade 緩存

        $manager2 = app('setting.manager');
        expect($manager2->getModel())->toBeInstanceOf(\Tests\Fixtures\CustomSetting::class);

        // 測試 3: 驗證不同模型的表名不同
        expect($manager1->getModel()->getTable())->toBe('settings');
        expect($manager2->getModel()->getTable())->toBe('custom_settings');

        // 測試 4: 檢查 Facade 實際使用的模型
        $facadeModel = Setting::getModel();
        expect($facadeModel)->toBeInstanceOf(\Tests\Fixtures\CustomSetting::class);
        expect($facadeModel->getTable())->toBe('custom_settings');

        // 測試 5: 驗證 Facade 能正確使用配置的模型
        Setting::set('facade_test', 'test_value');
        expect(Setting::get('facade_test'))->toBe('test_value');

        // 調試：檢查資料在哪個表中
        $defaultModel = new \Bleuren\LaravelSetting\Setting;
        $customModel = new \Tests\Fixtures\CustomSetting;

        $inDefaultTable = $defaultModel->where('key', 'facade_test')->exists();
        $inCustomTable = $customModel->where('key', 'facade_test')->exists();

        // 由於當前配置使用自定義模型，資料應該在 custom_settings 表中
        expect($inCustomTable)->toBeTrue('資料應該在 custom_settings 表中');
        expect($inDefaultTable)->toBeFalse('資料不應該在 settings 表中');
    });
});

describe('Setting Facade Integration', function () {

    it('integrates properly with Laravel cache system', function () {
        Setting::set('cache_integration_test', 'cached_value');

        // Clear application cache but keep memory cache
        cache()->flush();

        // Should still work due to memory cache
        expect(Setting::get('cache_integration_test'))->toBe('cached_value');
    });

    it('persists settings across requests simulation', function () {
        // Simulate first request
        Setting::set('persistence_test', 'persistent_value');

        // Clear memory cache to simulate new request
        Setting::clearMemoryCache();

        // Should still retrieve from database/cache
        expect(Setting::get('persistence_test'))->toBe('persistent_value');
    });

    it('handles concurrent setting operations', function () {
        // Simulate concurrent operations
        $operations = [];

        for ($i = 1; $i <= 10; $i++) {
            Setting::set("concurrent_test_{$i}", "value_{$i}");
            $operations[] = "concurrent_test_{$i}";
        }

        // Verify all operations completed successfully
        foreach ($operations as $key) {
            expect(Setting::has($key))->toBeTrue();
        }

        expect(Setting::all())->toHaveCount(10);
    });

    it('maintains cache consistency across operations', function () {
        // Set initial value
        Setting::set('consistency_test', 'initial_value');
        $initialCacheKey = Setting::cacheKey('consistency_test');

        // Verify it's cached
        expect(cache()->has($initialCacheKey))->toBeTrue();
        expect(cache()->get($initialCacheKey))->toBe('initial_value');

        // Update value
        Setting::set('consistency_test', 'updated_value');

        // Cache should be updated
        expect(cache()->get($initialCacheKey))->toBe('updated_value');
        expect(Setting::get('consistency_test'))->toBe('updated_value');

        // Remove setting
        Setting::remove('consistency_test');

        // Cache should be cleared
        expect(cache()->has($initialCacheKey))->toBeFalse();
        expect(Setting::get('consistency_test'))->toBeNull();
    });

    it('works with eager loading configuration', function () {
        // Configure eager loading
        config([
            'settings.eager_load' => true,
            'settings.eager_load_keys' => [
                'eager_test_1',
                'eager_test_2',
                'eager_test_3',
            ],
        ]);

        // Set the eager load keys
        Setting::set('eager_test_1', 'eager_value_1');
        Setting::set('eager_test_2', 'eager_value_2');
        Setting::set('eager_test_3', 'eager_value_3');
        Setting::set('not_eager_test', 'not_eager_value');

        // Clear memory cache
        Setting::clearMemoryCache();

        // Eager loaded keys should be available quickly
        expect(Setting::get('eager_test_1'))->toBe('eager_value_1');
        expect(Setting::get('eager_test_2'))->toBe('eager_value_2');
        expect(Setting::get('eager_test_3'))->toBe('eager_value_3');

        // Non-eager loaded key should still work
        expect(Setting::get('not_eager_test'))->toBe('not_eager_value');
    });
});
