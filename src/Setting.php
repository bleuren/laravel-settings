<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class Setting extends Model
{
    protected $fillable = [
        'key', 'description', 'value',
    ];

    /**
     * 保存已解析的設定值的記憶化緩存
     *
     * @var array<string, mixed>
     */
    protected static array $inMemoryCache = [];

    /**
     * 創建一個新的實例
     *
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        // 從配置中獲取自定義表名
        $this->table = Config::get('settings.table', 'settings');

        // 從配置中獲取自定義數據庫連接
        $connection = Config::get('settings.database_connection');
        if ($connection) {
            $this->connection = $connection;
        }

        parent::__construct($attributes);
    }

    /**
     * 獲取設定的完整緩存鍵名
     *
     * @param  string  $key  設定的鍵名
     * @return string 完整緩存鍵名
     */
    public static function cacheKey(string $key): string
    {
        $prefix = Config::get('settings.cache_prefix', 'settings.');

        return $prefix.$key;
    }

    /**
     * 模型啟動時註冊事件監聽器
     */
    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            $cacheKey = self::cacheKey($setting->key);
            Cache::forget($cacheKey);
            self::forgetFromMemory($setting->key);
        });

        static::deleted(function (Setting $setting) {
            $cacheKey = self::cacheKey($setting->key);
            Cache::forget($cacheKey);
            self::forgetFromMemory($setting->key);
        });
    }

    /**
     * 獲取設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $default  默認值
     * @return mixed 設定值或默認值
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        // 檢查記憶化緩存中是否已存在此鍵
        if (array_key_exists($key, self::$inMemoryCache)) {
            return self::$inMemoryCache[$key] ?? $default;
        }

        $cacheKey = self::cacheKey($key);

        try {
            $value = Cache::memo()->rememberForever($cacheKey, function () use ($key) {
                $setting = self::where('key', $key)->first();

                return $setting ? $setting->value : null;
            });

            // 將結果保存到記憶化緩存中
            self::$inMemoryCache[$key] = $value;

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
     * @return self 設定模型實例
     */
    public static function set(string $key, mixed $value, ?string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description ?? '']
        );

        $cacheKey = self::cacheKey($key);
        Cache::forever($cacheKey, $value);

        // 更新記憶化緩存
        self::$inMemoryCache[$key] = $value;

        return $setting;
    }

    /**
     * 批量設置設定值
     *
     * @param  array<string, mixed>  $settings  設定數組，格式為 ['key' => 'value', ...]
     * @param  string|null  $description  所有設定的預設描述
     * @return Collection<int, Setting> 設定模型實例集合
     */
    public static function setMany(array $settings, ?string $description = null): Collection
    {
        $models = collect();

        foreach ($settings as $key => $value) {
            $models->push(self::set($key, $value, $description));
        }

        return $models;
    }

    /**
     * 檢查設定是否存在
     *
     * @param  string  $key  設定鍵名
     * @return bool 設定是否存在
     */
    public static function has(string $key): bool
    {
        // 檢查記憶化緩存
        if (array_key_exists($key, self::$inMemoryCache)) {
            return true;
        }

        $cacheKey = self::cacheKey($key);

        try {
            return Cache::memo()->has($cacheKey) || self::where('key', $key)->exists();
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
    public static function remove(string $key): bool
    {
        $deleted = (bool) self::where('key', $key)->delete();

        $cacheKey = self::cacheKey($key);
        Cache::forget($cacheKey);
        self::forgetFromMemory($key);

        return $deleted;
    }

    /**
     * 從記憶化緩存中移除特定鍵
     *
     * @param  string  $key  設定鍵名
     */
    protected static function forgetFromMemory(string $key): void
    {
        unset(self::$inMemoryCache[$key]);
    }

    /**
     * 清除記憶化緩存
     */
    public static function clearMemoryCache(): void
    {
        self::$inMemoryCache = [];
    }
}
