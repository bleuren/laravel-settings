<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Facades\Setting;
use Tests\Fixtures\CustomSetting;

// 表創建已在 TestCase::setUpDatabase() 中處理

describe('Custom Setting Model', function () {

    it('can use HasSettings trait with custom model', function () {
        $customSetting = new CustomSetting;

        $result = $customSetting->setSetting('custom_test', 'custom_value', 'Custom description');

        expect($result)->toBeInstanceOf(CustomSetting::class);
        expect($customSetting->getSetting('custom_test'))->toBe('custom_value');
    });

    it('uses different table for custom model', function () {
        $customSetting = new CustomSetting;

        expect($customSetting->getTable())->toBe('custom_settings');
    });

    it('supports custom fields in custom model', function () {
        $customSetting = new CustomSetting;

        // Create with custom attributes
        $model = $customSetting->create([
            'key' => 'custom_field_test',
            'value' => 'custom_field_value',
            'description' => 'Custom field description',
            'category' => 'test_category',
            'is_public' => true,
        ]);

        expect($model->category)->toBe('test_category');
        expect($model->is_public)->toBeTrue();
    });

    it('can use custom model methods', function () {
        $customSetting = new CustomSetting;

        // Create settings with different categories
        $customSetting->create([
            'key' => 'cat1_setting1',
            'value' => 'value1',
            'category' => 'category1',
        ]);

        $customSetting->create([
            'key' => 'cat1_setting2',
            'value' => 'value2',
            'category' => 'category1',
        ]);

        $customSetting->create([
            'key' => 'cat2_setting1',
            'value' => 'value3',
            'category' => 'category2',
        ]);

        // Test custom method
        $category1Settings = $customSetting->getSettingsByCategory('category1');

        expect($category1Settings)->toHaveCount(2);
    });

    it('maintains separate caching for custom models', function () {
        $defaultModel = new \Bleuren\LaravelSetting\Setting;
        $customModel = new CustomSetting;

        // Set same key in both models
        $defaultModel->setSetting('shared_key', 'default_value');
        $customModel->setSetting('shared_key', 'custom_value');

        // Values should be independent
        expect($defaultModel->getSetting('shared_key'))->toBe('default_value');
        expect($customModel->getSetting('shared_key'))->toBe('custom_value');

        // Cache keys should be different
        $defaultCacheKey = $defaultModel->getCacheKey('shared_key');
        $customCacheKey = $customModel->getCacheKey('shared_key');

        expect($defaultCacheKey)->not->toBe($customCacheKey);
    });

    it('works with static method calls on custom model', function () {
        // Test static calls work on custom model
        CustomSetting::set('static_custom_test', 'static_custom_value');

        expect(CustomSetting::get('static_custom_test'))->toBe('static_custom_value');
        expect(CustomSetting::has('static_custom_test'))->toBeTrue();

        $result = CustomSetting::remove('static_custom_test');

        expect($result)->toBeTrue();
        expect(CustomSetting::has('static_custom_test'))->toBeFalse();
    });

    it('supports batch operations with custom model', function () {
        $customSetting = new CustomSetting;

        $settings = [
            'batch_custom1' => 'batch_value1',
            'batch_custom2' => 'batch_value2',
            'batch_custom3' => 'batch_value3',
        ];

        $result = $customSetting->setManySettings($settings, 'Batch custom description');

        expect($result)->toHaveCount(3);
        expect($customSetting->getSetting('batch_custom1'))->toBe('batch_value1');
        expect($customSetting->getSetting('batch_custom2'))->toBe('batch_value2');
        expect($customSetting->getSetting('batch_custom3'))->toBe('batch_value3');
    });
});

describe('Custom Model Configuration', function () {

    it('can be configured as default model', function () {
        config(['settings.model' => CustomSetting::class]);

        // Clear cached manager instance
        app()->forgetInstance('setting.manager');

        // Facade should now use custom model
        Setting::set('config_test', 'config_value');

        // Verify it's using custom model by checking table
        $manager = app('setting.manager');
        $model = $manager->getModel();

        expect($model)->toBeInstanceOf(CustomSetting::class);
        expect($model->getTable())->toBe('custom_settings');
        expect(Setting::get('config_test'))->toBe('config_value');
    });

    it('supports custom database connection configuration', function () {
        // This would typically be tested with multiple database connections
        // For now, we verify the table configuration works
        $customSetting = new CustomSetting;

        expect($customSetting->getTable())->toBe('custom_settings');
    });

    it('validates custom model requirements in SettingManager', function () {
        // Test that SettingManager validates model requirements

        // Valid custom model should work
        config(['settings.model' => CustomSetting::class]);
        expect(fn () => new \Bleuren\LaravelSetting\SettingManager)->not->toThrow(\Exception::class);

        // Invalid model (non-existent) should throw
        config(['settings.model' => 'NonExistentClass']);
        expect(fn () => new \Bleuren\LaravelSetting\SettingManager)
            ->toThrow(\InvalidArgumentException::class);
    });
});

describe('Custom Model Integration', function () {

    it('works with helper function when configured', function () {
        config(['settings.model' => CustomSetting::class]);
        app()->forgetInstance('setting.manager');

        Setting::set('helper_custom_test', 'helper_custom_value');

        // Helper function should work with custom model
        expect(setting('helper_custom_test'))->toBe('helper_custom_value');
    });

    it('maintains data integrity with unique constraints', function () {
        $customModel = new CustomSetting;

        // Create first setting
        $customModel->create([
            'key' => 'unique_test',
            'value' => 'first_value',
            'category' => 'test_category',
        ]);

        // Try to create duplicate (should update instead)
        $customModel->setSetting('unique_test', 'updated_value');

        // Should have updated, not created duplicate
        $settings = $customModel->where('key', 'unique_test')->get();
        expect($settings)->toHaveCount(1);
        expect($customModel->getSetting('unique_test'))->toBe('updated_value');
    });

    it('supports complex queries with custom model features', function () {
        $customModel = new CustomSetting;

        // Create settings with different visibility
        $customModel->create([
            'key' => 'public1',
            'value' => 'public_value1',
            'is_public' => true,
        ]);

        $customModel->create([
            'key' => 'private1',
            'value' => 'private_value1',
            'is_public' => false,
        ]);

        $customModel->create([
            'key' => 'public2',
            'value' => 'public_value2',
            'is_public' => true,
        ]);

        // Test custom method
        $publicSettings = $customModel->getPublicSettings();

        expect($publicSettings)->toHaveCount(2);

        // Verify all returned settings are public
        foreach ($publicSettings as $setting) {
            expect($setting->is_public)->toBeTrue();
        }
    });

    it('works with eager loading configuration', function () {
        config([
            'settings.model' => CustomSetting::class,
            'settings.eager_load' => true,
            'settings.eager_load_keys' => [
                'eager_custom_1',
                'eager_custom_2',
            ],
        ]);

        app()->forgetInstance('setting.manager');

        // Set eager load settings
        Setting::set('eager_custom_1', 'eager_custom_value_1');
        Setting::set('eager_custom_2', 'eager_custom_value_2');

        // Clear memory cache to test eager loading
        Setting::clearMemoryCache();

        // These should be available through eager loading
        expect(Setting::get('eager_custom_1'))->toBe('eager_custom_value_1');
        expect(Setting::get('eager_custom_2'))->toBe('eager_custom_value_2');
    });
});
