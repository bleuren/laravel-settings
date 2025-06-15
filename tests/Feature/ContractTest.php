<?php

declare(strict_types=1);

use Bleuren\LaravelSetting\Contracts\SettingRepository;
use Bleuren\LaravelSetting\SettingManager;

describe('SettingRepository Contract', function () {

    it('can resolve contract from service container', function () {
        $repository = app(SettingRepository::class);

        expect($repository)->toBeInstanceOf(SettingRepository::class);
        expect($repository)->toBeInstanceOf(SettingManager::class);
    });

    it('contract and manager resolve to same instance', function () {
        $repository = app(SettingRepository::class);
        $manager = app(SettingManager::class);

        expect($repository)->toBe($manager);
    });

    it('can be injected via constructor', function () {
        // 創建一個測試服務類來驗證依賴注入
        $testService = new class(app(SettingRepository::class))
        {
            public function __construct(
                private SettingRepository $settings
            ) {}

            public function getSettings(): SettingRepository
            {
                return $this->settings;
            }

            public function testSetting(): string
            {
                $this->settings->set('contract_test', 'contract_value');

                return $this->settings->get('contract_test');
            }
        };

        expect($testService->getSettings())->toBeInstanceOf(SettingRepository::class);
        expect($testService->testSetting())->toBe('contract_value');
    });

    it('contract methods work correctly', function () {
        $repository = app(SettingRepository::class);

        // 測試所有 Contract 方法
        $model = $repository->set('contract_key', 'contract_value', 'Contract test');
        expect($model)->toBeInstanceOf(\Illuminate\Database\Eloquent\Model::class);

        expect($repository->get('contract_key'))->toBe('contract_value');
        expect($repository->has('contract_key'))->toBeTrue();

        $cacheKey = $repository->cacheKey('contract_key');
        expect($cacheKey)->toBeString();
        expect($cacheKey)->toContain('contract_key');

        $allSettings = $repository->all();
        expect($allSettings)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        $searchResults = $repository->search('contract_%');
        expect($searchResults)->toBeInstanceOf(\Illuminate\Support\Collection::class);

        expect($repository->remove('contract_key'))->toBeTrue();
        expect($repository->has('contract_key'))->toBeFalse();
    });

    it('contract supports batch operations', function () {
        $repository = app(SettingRepository::class);

        $settings = [
            'batch_contract_1' => 'value1',
            'batch_contract_2' => 'value2',
            'batch_contract_3' => 'value3',
        ];

        $results = $repository->setMany($settings, 'Batch contract test');

        expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($results)->toHaveCount(3);

        expect($repository->get('batch_contract_1'))->toBe('value1');
        expect($repository->get('batch_contract_2'))->toBe('value2');
        expect($repository->get('batch_contract_3'))->toBe('value3');
    });

    it('contract supports memory cache operations', function () {
        $repository = app(SettingRepository::class);

        $repository->set('cache_contract_test', 'cached_value');

        // 清除記憶體緩存應該不會拋出例外
        expect(fn () => $repository->clearMemoryCache())->not->toThrow(\Exception::class);

        // 值應該仍然可以從持久化存儲中獲取
        expect($repository->get('cache_contract_test'))->toBe('cached_value');
    });

    it('can be used in service provider registration', function () {
        // 測試 Contract 可以在服務提供者中使用
        $testProvider = new class(app()) extends \Illuminate\Support\ServiceProvider
        {
            public function register()
            {
                $this->app->singleton('test.contract.service', function ($app) {
                    return new class($app[SettingRepository::class])
                    {
                        public function __construct(
                            private SettingRepository $settings
                        ) {}

                        public function testContractUsage(): bool
                        {
                            $this->settings->set('provider_test', 'provider_value');

                            return $this->settings->has('provider_test');
                        }
                    };
                });
            }
        };

        app()->register($testProvider);

        $testService = app('test.contract.service');
        expect($testService->testContractUsage())->toBeTrue();
    });

    it('contract binding is consistent across requests', function () {
        // 模擬多次請求
        $instances = [];

        for ($i = 0; $i < 5; $i++) {
            $instances[] = app(SettingRepository::class);
        }

        // 所有實例應該是同一個（單例模式）
        $firstInstance = $instances[0];
        foreach ($instances as $instance) {
            expect($instance)->toBe($firstInstance);
        }
    });
});
