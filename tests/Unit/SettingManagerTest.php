<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Setting;
use Bleuren\LaravelSetting\SettingManager;
use Tests\Fixtures\CustomSetting;

// 表創建已在 TestCase::setUpDatabase() 中處理

describe('SettingManager', function () {

    it('can be instantiated with default Setting model', function () {
        $manager = new SettingManager;

        expect($manager->getModel())->toBeInstanceOf(Setting::class);
    });

    it('can be instantiated with custom model via config', function () {
        config(['settings.model' => CustomSetting::class]);

        $manager = new SettingManager;

        expect($manager->getModel())->toBeInstanceOf(CustomSetting::class);
    });

    it('throws exception for non-existent model class', function () {
        config(['settings.model' => 'NonExistentClass']);

        expect(fn () => new SettingManager)
            ->toThrow(\InvalidArgumentException::class, '設定模型類別 [NonExistentClass] 不存在');
    });

    it('throws exception for model not extending Eloquent Model', function () {
        config(['settings.model' => \stdClass::class]);

        expect(fn () => new SettingManager)
            ->toThrow(\InvalidArgumentException::class, '設定模型必須繼承 Illuminate\\Database\\Eloquent\\Model');
    });

    it('throws exception for model not using HasSettings trait', function () {
        // 創建一個不使用 HasSettings trait 的模型類
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            protected $table = 'test_table';
        };

        config(['settings.model' => get_class($modelClass)]);

        expect(fn () => new SettingManager)
            ->toThrow(\InvalidArgumentException::class, '設定模型必須使用 HasSettings trait');
    });

    it('delegates get method to model', function () {
        $manager = new SettingManager;

        // 透過管理器設定值
        $manager->set('manager_test', 'manager_value');

        // 透過管理器獲取值
        $value = $manager->get('manager_test');

        expect($value)->toBe('manager_value');
    });

    it('delegates set method to model', function () {
        $manager = new SettingManager;

        $result = $manager->set('test_key', 'test_value', 'Test description');

        expect($result)->toBeInstanceOf(Setting::class);
        expect($manager->get('test_key'))->toBe('test_value');
    });

    it('delegates setMany method to model', function () {
        $manager = new SettingManager;

        $settings = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $result = $manager->setMany($settings);

        expect($result)->toHaveCount(2);
        expect($manager->get('key1'))->toBe('value1');
        expect($manager->get('key2'))->toBe('value2');
    });

    it('delegates has method to model', function () {
        $manager = new SettingManager;

        expect($manager->has('non_existent'))->toBeFalse();

        $manager->set('exists_test', 'value');

        expect($manager->has('exists_test'))->toBeTrue();
    });

    it('delegates remove method to model', function () {
        $manager = new SettingManager;

        $manager->set('remove_test', 'value');
        expect($manager->has('remove_test'))->toBeTrue();

        $result = $manager->remove('remove_test');

        expect($result)->toBeTrue();
        expect($manager->has('remove_test'))->toBeFalse();
    });

    it('delegates cacheKey method to model', function () {
        $manager = new SettingManager;

        $cacheKey = $manager->cacheKey('test_key');

        expect($cacheKey)->toContain('test_settings.Setting.test_key');
    });

    it('delegates clearMemoryCache method to model', function () {
        $manager = new SettingManager;

        // 設定一些值來建立記憶體緩存
        $manager->set('cache_test1', 'value1');
        $manager->set('cache_test2', 'value2');

        // 清除記憶體緩存應該不會拋出例外
        expect(fn () => $manager->clearMemoryCache())->not->toThrow(\Exception::class);
    });

    it('delegates all method to model', function () {
        $manager = new SettingManager;

        $manager->set('all_test1', 'value1');
        $manager->set('all_test2', 'value2');

        $allSettings = $manager->all();

        expect($allSettings)->toHaveCount(2);
    });

    it('delegates search method to model', function () {
        $manager = new SettingManager;

        $manager->set('search_app_name', 'App Name');
        $manager->set('search_app_version', '1.0.0');
        $manager->set('search_user_theme', 'dark');

        $results = $manager->search('search_app_%');

        expect($results)->toHaveCount(2);
    });

    it('supports dynamic method calls via __call', function () {
        config(['settings.model' => CustomSetting::class]);
        $manager = new SettingManager;

        // 設置一些測試資料，包含 category 欄位
        $manager->set('test1', 'value1');
        $manager->set('test2', 'value2');

        // 直接在模型上設置帶有 category 的資料
        $model = $manager->getModel();
        $model->create([
            'key' => 'cat_test1',
            'value' => 'cat_value1',
            'category' => 'test_category',
        ]);
        $model->create([
            'key' => 'cat_test2',
            'value' => 'cat_value2',
            'category' => 'test_category',
        ]);

        // 使用自定義方法
        $categorySettings = $manager->getSettingsByCategory('test_category');

        expect($categorySettings)->toHaveCount(2);
        expect($categorySettings->pluck('key')->toArray())->toContain('cat_test1', 'cat_test2');
    });

    it('works correctly with custom model features', function () {
        config(['settings.model' => CustomSetting::class]);
        $manager = new SettingManager;

        // 測試自定義模型具有不同的緩存鍵格式
        $cacheKey = $manager->cacheKey('custom_test');

        expect($cacheKey)->toContain('Tests\Fixtures\CustomSetting');
        expect($cacheKey)->not->toContain('test_settings.custom_test');
    });
});

describe('SettingManager Integration', function () {

    it('maintains separate cache spaces for different models', function () {
        // 使用默認模型
        $defaultManager = new SettingManager;
        $defaultManager->set('shared_key', 'default_value');

        // 使用自定義模型
        config(['settings.model' => CustomSetting::class]);
        $customManager = new SettingManager;
        $customManager->set('shared_key', 'custom_value');

        // 驗證兩個模型的值是獨立的
        expect($defaultManager->get('shared_key'))->toBe('default_value');
        expect($customManager->get('shared_key'))->toBe('custom_value');
    });

    it('respects model-specific database table configuration', function () {
        $manager = new SettingManager;
        $model = $manager->getModel();

        expect($model->getTable())->toBe('settings');

        // 測試自定義模型使用不同表
        config(['settings.model' => CustomSetting::class]);
        $customManager = new SettingManager;
        $customModel = $customManager->getModel();

        expect($customModel->getTable())->toBe('custom_settings');
    });
});
