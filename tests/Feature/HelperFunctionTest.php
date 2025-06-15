<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Facades\Setting;

// 表創建已在 TestCase::setUpDatabase() 中處理

describe('setting() helper function', function () {

    it('exists and is callable', function () {
        expect(function_exists('setting'))->toBeTrue();
    });

    it('can get setting values', function () {
        Setting::set('helper_test', 'helper_value');

        $value = setting('helper_test');

        expect($value)->toBe('helper_value');
    });

    it('returns default value when setting does not exist', function () {
        $value = setting('non_existent_helper', 'default_helper_value');

        expect($value)->toBe('default_helper_value');
    });

    it('returns null when no default is provided and setting does not exist', function () {
        $value = setting('non_existent_helper_null');

        expect($value)->toBeNull();
    });

    it('works with various data types', function () {
        // String
        Setting::set('helper_string', 'string_value');
        expect(setting('helper_string'))->toBe('string_value');

        // Integer
        Setting::set('helper_int', '123');
        expect(setting('helper_int'))->toBe('123');

        // Boolean (stored as string in database)
        Setting::set('helper_bool', 'true');
        expect(setting('helper_bool'))->toBe('true');

        // Array (JSON encoded in database)
        Setting::set('helper_array', json_encode(['key' => 'value']));
        expect(setting('helper_array'))->toBe(json_encode(['key' => 'value']));
    });

    it('respects different default value types', function () {
        expect(setting('non_existent', 'string_default'))->toBe('string_default');
        expect(setting('non_existent', 42))->toBe(42);
        expect(setting('non_existent', true))->toBe(true);
        expect(setting('non_existent', ['array']))->toBe(['array']);
        expect(setting('non_existent', null))->toBeNull();
    });

    it('is a shortcut to Setting facade get method', function () {
        Setting::set('shortcut_test', 'shortcut_value');

        $helperValue = setting('shortcut_test');
        $facadeValue = Setting::get('shortcut_test');

        expect($helperValue)->toBe($facadeValue);
    });

    it('works with complex setting keys', function () {
        $complexKey = 'app.modules.user_management.enabled';
        Setting::set($complexKey, 'complex_value');

        $value = setting($complexKey);

        expect($value)->toBe('complex_value');
    });

    it('handles special characters in keys', function () {
        $specialKey = 'key_with-special.chars@domain';
        Setting::set($specialKey, 'special_value');

        $value = setting($specialKey);

        expect($value)->toBe('special_value');
    });
});

describe('helper function integration', function () {

    it('works with caching like facade', function () {
        Setting::set('cached_helper_test', 'cached_helper_value');

        // First call (should cache)
        $value1 = setting('cached_helper_test');

        // Second call (should use cache)
        $value2 = setting('cached_helper_test');

        expect($value1)->toBe('cached_helper_value');
        expect($value2)->toBe('cached_helper_value');
    });

    it('reflects real-time changes', function () {
        // Initial value
        Setting::set('realtime_test', 'initial_value');
        expect(setting('realtime_test'))->toBe('initial_value');

        // Update value
        Setting::set('realtime_test', 'updated_value');
        expect(setting('realtime_test'))->toBe('updated_value');

        // Remove value
        Setting::remove('realtime_test');
        expect(setting('realtime_test', 'fallback'))->toBe('fallback');
    });

    it('works in combination with facade operations', function () {
        // Set via facade
        Setting::set('combo_test', 'facade_set_value');

        // Get via helper
        expect(setting('combo_test'))->toBe('facade_set_value');

        // Update via facade
        Setting::set('combo_test', 'facade_updated_value');

        // Verify via helper
        expect(setting('combo_test'))->toBe('facade_updated_value');
    });

    it('works with custom models through configuration', function () {
        // Change model configuration
        config(['settings.model' => Tests\Fixtures\CustomSetting::class]);

        // Clear any cached manager instance
        app()->forgetInstance('setting.manager');

        // Set value through facade (which uses the new model)
        Setting::set('custom_model_test', 'custom_model_value');

        // Get via helper (should work with custom model)
        expect(setting('custom_model_test'))->toBe('custom_model_value');
    });
});
