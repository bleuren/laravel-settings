<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\CustomSetting;

// 表創建已在 TestCase::setUpDatabase() 中處理

describe('HasSettings Trait', function () {

    it('can get and set settings', function () {
        $setting = new Setting;

        $result = $setting->setSetting('test_key', 'test_value', 'Test description');

        expect($result)->toBeInstanceOf(Setting::class);
        expect($setting->getSetting('test_key'))->toBe('test_value');
    });

    it('returns default value when setting does not exist', function () {
        $setting = new Setting;

        expect($setting->getSetting('non_existent_key', 'default'))->toBe('default');
    });

    it('can check if setting exists', function () {
        $setting = new Setting;

        // 清理測試環境
        $setting->where('key', 'test_key')->delete();
        $setting->clearMemoryCache();
        \Illuminate\Support\Facades\Cache::flush();

        expect($setting->hasSetting('test_key'))->toBeFalse();

        $setting->setSetting('test_key', 'value');

        expect($setting->hasSetting('test_key'))->toBeTrue();
    });

    it('can set many settings at once', function () {
        $setting = new Setting;

        $settings = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $result = $setting->setManySettings($settings, 'Batch description');

        expect($result)->toHaveCount(3);
        expect($setting->getSetting('key1'))->toBe('value1');
        expect($setting->getSetting('key2'))->toBe('value2');
        expect($setting->getSetting('key3'))->toBe('value3');
    });

    it('can remove settings', function () {
        $setting = new Setting;

        $setting->setSetting('test_key', 'test_value');
        expect($setting->hasSetting('test_key'))->toBeTrue();

        $result = $setting->removeSetting('test_key');

        expect($result)->toBeTrue();
        expect($setting->hasSetting('test_key'))->toBeFalse();
    });

    it('can get all settings', function () {
        $setting = new Setting;

        $setting->setSetting('key1', 'value1');
        $setting->setSetting('key2', 'value2');

        $allSettings = $setting->getAllSettings();

        expect($allSettings)->toHaveCount(2);
    });

    it('can search settings by pattern', function () {
        $setting = new Setting;

        $setting->setSetting('app_name', 'My App');
        $setting->setSetting('app_version', '1.0.0');
        $setting->setSetting('user_theme', 'dark');

        $appSettings = $setting->searchSettings('app_%');

        expect($appSettings)->toHaveCount(2);
    });

    it('uses memory cache for performance', function () {
        $setting = new Setting;

        // 首次設定
        $setting->setSetting('cached_key', 'cached_value');

        // 清除靜態記憶體緩存來測試
        $setting->clearMemoryCache();

        // 第一次獲取會查詢資料庫
        $value1 = $setting->getSetting('cached_key');

        // 第二次獲取應該使用記憶體緩存
        $value2 = $setting->getSetting('cached_key');

        expect($value1)->toBe('cached_value');
        expect($value2)->toBe('cached_value');
    });

    it('generates correct cache keys', function () {
        $setting = new Setting;
        $customSetting = new CustomSetting;

        $settingCacheKey = $setting->getCacheKey('test_key');
        $customCacheKey = $customSetting->getCacheKey('test_key');

        expect($settingCacheKey)->toContain('test_settings.Setting.test_key');
        expect($customCacheKey)->toContain('Tests\Fixtures\CustomSetting');
        expect($settingCacheKey)->not->toBe($customCacheKey);
    });

    it('handles database exceptions gracefully', function () {
        $setting = new Setting;

        // 模擬資料庫錯誤的情況
        Schema::drop('settings');

        $result = $setting->getSetting('test_key', 'fallback');

        expect($result)->toBe('fallback');
    });

    it('works with custom model', function () {
        $customSetting = new CustomSetting;

        $result = $customSetting->setSetting('custom_key', 'custom_value', 'Custom description');

        expect($result)->toBeInstanceOf(CustomSetting::class);
        expect($customSetting->getSetting('custom_key'))->toBe('custom_value');
    });
});

describe('HasSettings Magic Methods', function () {

    it('supports static method calls via __callStatic', function () {
        // 測試 get 方法映射
        Setting::set('static_test', 'static_value');
        $value = Setting::get('static_test');

        expect($value)->toBe('static_value');
    });

    it('supports has method via static call', function () {
        Setting::set('exists_test', 'value');

        expect(Setting::has('exists_test'))->toBeTrue();
        expect(Setting::has('non_exists_test'))->toBeFalse();
    });

    it('supports remove method via static call', function () {
        Setting::set('remove_test', 'value');
        expect(Setting::has('remove_test'))->toBeTrue();

        $result = Setting::remove('remove_test');

        expect($result)->toBeTrue();
        expect(Setting::has('remove_test'))->toBeFalse();
    });

    it('supports setMany method via static call', function () {
        $settings = [
            'batch_key1' => 'batch_value1',
            'batch_key2' => 'batch_value2',
        ];

        $result = Setting::setMany($settings);

        expect($result)->toHaveCount(2);
        expect(Setting::get('batch_key1'))->toBe('batch_value1');
    });

    it('throws exception for non-existent static methods', function () {
        expect(fn () => Setting::nonExistentMethod())
            ->toThrow(\BadMethodCallException::class);
    });
});

describe('HasSettings Cache Integration', function () {

    it('caches settings properly', function () {
        $setting = new Setting;

        // 設定一個值
        $setting->setSetting('cache_test', 'cached_value');

        // 驗證快取中有這個值
        $cacheKey = $setting->getCacheKey('cache_test');
        expect(Cache::has($cacheKey))->toBeTrue();
        expect(Cache::get($cacheKey))->toBe('cached_value');
    });

    it('invalidates cache when setting is updated', function () {
        $setting = new Setting;

        // 設定初始值
        $setting->setSetting('update_test', 'initial_value');
        $cacheKey = $setting->getCacheKey('update_test');

        // 確認快取存在
        expect(Cache::has($cacheKey))->toBeTrue();

        // 更新值
        $setting->setSetting('update_test', 'updated_value');

        // 驗證新值
        expect($setting->getSetting('update_test'))->toBe('updated_value');
    });

    it('invalidates cache when setting is deleted', function () {
        $setting = new Setting;

        // 設定值
        $setting->setSetting('delete_test', 'value');
        $cacheKey = $setting->getCacheKey('delete_test');

        // 確認快取存在
        expect(Cache::has($cacheKey))->toBeTrue();

        // 刪除設定
        $setting->removeSetting('delete_test');

        // 驗證值已不存在
        expect($setting->getSetting('delete_test'))->toBeNull();
    });
});
