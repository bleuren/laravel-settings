<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

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
     *
     * @var array<string, mixed>
     */
    protected static array $inMemoryCache = [];

    /**
     * 初始化 trait
     */
    public function initializeHasSettings(): void
    {
        // 設定可填充的欄位
        $this->fillable = array_merge($this->fillable, [
            'key', 'description', 'value',
        ]);
    }

    /**
     * 模型啟動時註冊事件監聽器
     */
    protected static function bootHasSettings(): void
    {
        static::saved(function (Model $model) {
            if ($model->hasSettingsFeature()) {
                $cacheKey = $model->getCacheKey($model->key);
                Cache::forget($cacheKey);
                $model->forgetFromMemory($model->key);
            }
        });

        static::deleted(function (Model $model) {
            if ($model->hasSettingsFeature()) {
                $cacheKey = $model->getCacheKey($model->key);
                Cache::forget($cacheKey);
                $model->forgetFromMemory($model->key);
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
     *
     * @param  string  $key  設定的鍵名
     * @return string 完整緩存鍵名
     */
    public function getCacheKey(string $key): string
    {
        $prefix = Config::get('settings.cache_prefix', 'settings.');
        $modelClass = get_class($this);

        return $prefix.$modelClass.'.'.$key;
    }

    /**
     * 獲取設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $default  默認值
     * @return mixed 設定值或默認值
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);

        // 檢查記憶化緩存中是否已存在此鍵
        if (array_key_exists($cacheKey, self::$inMemoryCache)) {
            return self::$inMemoryCache[$cacheKey] ?? $default;
        }

        try {
            $value = Cache::memo()->rememberForever($cacheKey, function () use ($key) {
                $setting = $this->where('key', $key)->first();

                return $setting ? $setting->value : null;
            });

            // 將結果保存到記憶化緩存中
            self::$inMemoryCache[$cacheKey] = $value;

            return $value ?? $default;
        } catch (\Throwable $e) {
            report($e);

            return $default;
        }
    }

    /**
     * 設置設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $value  設定值
     * @param  string|null  $description  描述
     * @return static 設定模型實例
     */
    public function setSetting(string $key, mixed $value, ?string $description = null): static
    {
        $setting = $this->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description ?? '']
        );

        $cacheKey = $this->getCacheKey($key);
        Cache::forever($cacheKey, $value);

        // 更新記憶化緩存
        self::$inMemoryCache[$cacheKey] = $value;

        return $setting;
    }

    /**
     * 批量設置設定值
     *
     * @param  array<string, mixed>  $settings  設定數組，格式為 ['key' => 'value', ...]
     * @param  string|null  $description  所有設定的預設描述
     * @return Collection<int, static> 設定模型實例集合
     */
    public function setManySettings(array $settings, ?string $description = null): Collection
    {
        $models = collect();

        foreach ($settings as $key => $value) {
            $models->push($this->setSetting($key, $value, $description));
        }

        return $models;
    }

    /**
     * 檢查設定是否存在
     *
     * @param  string  $key  設定鍵名
     * @return bool 設定是否存在
     */
    public function hasSetting(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);

        // 檢查記憶化緩存
        if (array_key_exists($cacheKey, self::$inMemoryCache)) {
            return true;
        }

        try {
            return Cache::memo()->has($cacheKey) || $this->where('key', $key)->exists();
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /**
     * 刪除設定
     *
     * @param  string  $key  設定鍵名
     * @return bool 刪除是否成功
     */
    public function removeSetting(string $key): bool
    {
        $deleted = (bool) $this->where('key', $key)->delete();

        $cacheKey = $this->getCacheKey($key);
        Cache::forget($cacheKey);
        $this->forgetFromMemory($key);

        return $deleted;
    }

    /**
     * 從記憶化緩存中移除特定鍵
     *
     * @param  string  $key  設定鍵名
     */
    protected function forgetFromMemory(string $key): void
    {
        $cacheKey = $this->getCacheKey($key);
        unset(self::$inMemoryCache[$cacheKey]);
    }

    /**
     * 清除記憶化緩存
     */
    public function clearMemoryCache(): void
    {
        self::$inMemoryCache = [];
    }

    /**
     * 獲取所有設定
     *
     * @return Collection<int, static>
     */
    public function getAllSettings(): Collection
    {
        return $this->all();
    }

    /**
     * 根據鍵名模式搜索設定
     *
     * @param  string  $pattern  搜索模式
     * @return Collection<int, static>
     */
    public function searchSettings(string $pattern): Collection
    {
        return $this->where('key', 'LIKE', $pattern)->get();
    }

    /**
     * 支援靜態方法調用的魔術方法
     * 這允許在模型上使用靜態方法來調用實例方法
     */
    public static function __callStatic(string $method, array $parameters): mixed
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
