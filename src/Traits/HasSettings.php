<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * HasSettings Trait
 *
 * 提供設定功能給任何 Eloquent 模型使用
 * 使用此 trait 的模型需要包含以下欄位：
 * - key (string): 設定鍵名
 * - value (text): 設定值
 * - description (string, nullable): 設定描述
 */
trait HasSettings
{
    /**
     * 保存已解析的設定值的記憶化緩存
     * 使用模型類別名稱作為鍵來隔離不同模型的緩存
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $inMemoryCache = [];

    /**
     * 模型啟動時註冊事件監聽器
     */
    protected static function bootHasSettings(): void
    {
        static::saved(function (Model $model) {
            if ($model->hasSettingsFeature()) {
                $model->invalidateSettingCache($model->key);
            }
        });

        static::deleted(function (Model $model) {
            if ($model->hasSettingsFeature()) {
                $model->invalidateSettingCache($model->key);
            }
        });
    }

    /**
     * 檢查模型是否具有設定功能
     */
    public function hasSettingsFeature(): bool
    {
        return isset($this->key);
    }

    /**
     * 獲取設定的完整緩存鍵名
     */
    public function getCacheKey(string $key): string
    {
        $prefix = Config::get('settings.cache_prefix', 'settings.');
        $modelClass = get_class($this);

        return $prefix.$modelClass.'.'.$key;
    }

    /**
     * 獲取模型專用的記憶化緩存鍵
     */
    protected function getMemoryCacheKey(string $key): string
    {
        return get_class($this).':'.$key;
    }

    /**
     * 獲取設定值
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $memoryCacheKey = $this->getMemoryCacheKey($key);

        // 檢查記憶化緩存
        if (array_key_exists($memoryCacheKey, self::$inMemoryCache)) {
            return self::$inMemoryCache[$memoryCacheKey] ?? $default;
        }

        try {
            $cacheKey = $this->getCacheKey($key);

            // 使用永久緩存，因為設定通常是長期存在的
            $value = Cache::rememberForever($cacheKey, function () use ($key) {
                $setting = $this->where('key', $key)->first();

                return $setting?->value;
            });

            // 保存到記憶化緩存
            self::$inMemoryCache[$memoryCacheKey] = $value;

            return $value ?? $default;
        } catch (\Throwable $e) {
            report($e);

            return $default;
        }
    }

    /**
     * 設置設定值
     */
    public function setSetting(string $key, mixed $value, ?string $description = null): static
    {
        $setting = $this->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description ?? '']
        );

        // 更新緩存
        $this->updateSettingCache($key, $value);

        return $setting;
    }

    /**
     * 批量設置設定值（優化版本）
     */
    public function setManySettings(array $settings, ?string $description = null): Collection
    {
        if (empty($settings)) {
            return collect();
        }

        $models = collect();
        $now = now();

        try {
            DB::transaction(function () use ($settings, $description, &$models, $now) {
                foreach ($settings as $key => $value) {
                    // 使用 upsert 進行批量操作（如果資料庫支援）
                    $setting = $this->updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => $value,
                            'description' => $description ?? '',
                            'updated_at' => $now,
                        ]
                    );

                    $models->push($setting);

                    // 更新緩存
                    $this->updateSettingCache($key, $value);
                }
            });
        } catch (\Throwable $e) {
            report($e);
            throw $e;
        }

        return $models;
    }

    /**
     * 檢查設定是否存在
     */
    public function hasSetting(string $key): bool
    {
        $memoryCacheKey = $this->getMemoryCacheKey($key);

        // 檢查記憶化緩存
        if (array_key_exists($memoryCacheKey, self::$inMemoryCache)) {
            return self::$inMemoryCache[$memoryCacheKey] !== null;
        }

        try {
            $cacheKey = $this->getCacheKey($key);

            // 檢查 Laravel 緩存中是否有存在性標記
            $existsCacheKey = $cacheKey.':exists';

            if (Cache::has($existsCacheKey)) {
                $exists = Cache::get($existsCacheKey);
                if (! $exists) {
                    self::$inMemoryCache[$memoryCacheKey] = null;
                }

                return $exists;
            }

            // 檢查資料庫
            $exists = $this->where('key', $key)->exists();

            // 緩存存在性結果
            Cache::forever($existsCacheKey, $exists);

            if (! $exists) {
                self::$inMemoryCache[$memoryCacheKey] = null;
            }

            return $exists;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * 刪除設定
     */
    public function removeSetting(string $key): bool
    {
        try {
            $deleted = (bool) $this->where('key', $key)->delete();

            if ($deleted) {
                $this->invalidateSettingCache($key);
            }

            return $deleted;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * 更新設定緩存
     */
    protected function updateSettingCache(string $key, mixed $value): void
    {
        $cacheKey = $this->getCacheKey($key);
        $memoryCacheKey = $this->getMemoryCacheKey($key);

        // 更新 Laravel 緩存
        Cache::forever($cacheKey, $value);
        Cache::forever($cacheKey.':exists', true);

        // 更新記憶化緩存
        self::$inMemoryCache[$memoryCacheKey] = $value;
    }

    /**
     * 清除設定緩存
     */
    protected function invalidateSettingCache(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        $memoryCacheKey = $this->getMemoryCacheKey($key);

        // 清除 Laravel 緩存
        Cache::forget($cacheKey);
        Cache::forget($cacheKey.':exists');

        // 清除記憶化緩存
        unset(self::$inMemoryCache[$memoryCacheKey]);
    }

    /**
     * 從記憶化緩存中移除特定鍵
     */
    protected function forgetFromMemory(string $key): void
    {
        $memoryCacheKey = $this->getMemoryCacheKey($key);
        unset(self::$inMemoryCache[$memoryCacheKey]);
    }

    /**
     * 清除記憶化緩存
     */
    public function clearMemoryCache(): void
    {
        $modelClass = get_class($this);

        // 只清除當前模型的緩存
        foreach (self::$inMemoryCache as $key => $value) {
            if (str_starts_with($key, $modelClass.':')) {
                unset(self::$inMemoryCache[$key]);
            }
        }
    }

    /**
     * 獲取所有設定
     */
    public function getAllSettings(): Collection
    {
        return $this->all();
    }

    /**
     * 根據鍵名模式搜索設定
     */
    public function searchSettings(string $pattern): Collection
    {
        return $this->where('key', 'LIKE', $pattern)->get();
    }

    /**
     * 支援靜態方法調用的魔術方法
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        // 映射常用的靜態方法到實例方法
        $methodMap = [
            'get' => 'getSetting',
            'set' => 'setSetting',
            'setMany' => 'setManySettings',
            'has' => 'hasSetting',
            'remove' => 'removeSetting',
            'all' => 'getAllSettings',
            'search' => 'searchSettings',
        ];

        if (isset($methodMap[$method])) {
            return $instance->{$methodMap[$method]}(...$parameters);
        }

        // 如果沒有映射，直接嘗試調用方法
        if (method_exists($instance, $method)) {
            return $instance->$method(...$parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on ".static::class);
    }
}
