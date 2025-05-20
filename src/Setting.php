<?php

declare(strict_types=1);

namespace Bleuren\Setting;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key', 'description', 'value',
    ];

    /**
     * Generates the full cache key for a given setting key.
     *
     * @param  string  $key  The key of the setting.
     * @return string The full cache key.
     */
    public static function cacheKey(string $key): string
    {
        $prefix = config('settings.cache_prefix', 'settings.');

        return $prefix.$key;
    }

    protected static function booted(): void
    {
        static::saved(function (Setting $setting) {
            $cacheKey = self::cacheKey($setting->key);
            Cache::forget($cacheKey);
        });

        static::deleted(function (Setting $setting) {
            $cacheKey = self::cacheKey($setting->key);
            Cache::forget($cacheKey);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::cacheKey($key);

        try {
            return Cache::rememberForever($cacheKey, function () use ($key) {
                $setting = self::where('key', $key)->first();

                return $setting ? $setting->value : null;
            }) ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value, ?string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'description' => $description]
        );

        $cacheKey = self::cacheKey($key);
        Cache::forever($cacheKey, $value);

        return $setting;
    }
}
