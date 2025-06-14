<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static mixed get(string $key, mixed $default = null) 獲取設定值
 * @method static \Illuminate\Database\Eloquent\Model set(string $key, mixed $value, ?string $description = null) 設置設定值
 * @method static \Illuminate\Support\Collection setMany(array $settings, ?string $description = null) 批量設置設定值
 * @method static bool has(string $key) 檢查設定是否存在
 * @method static bool remove(string $key) 刪除設定
 * @method static void clearMemoryCache() 清除記憶化緩存
 * @method static string cacheKey(string $key) 獲取設定的完整緩存鍵名
 * @method static \Illuminate\Database\Eloquent\Model getModel() 獲取模型實例
 * @method static \Illuminate\Support\Collection all() 獲取所有設定
 * @method static \Illuminate\Support\Collection search(string $pattern) 根據鍵名模式搜索設定
 *
 * @see \Bleuren\LaravelSetting\Contracts\SettingRepository
 * @see \Bleuren\LaravelSetting\SettingManager
 */
class Setting extends Facade
{
    /**
     * 獲取門面的註冊名稱
     */
    protected static function getFacadeAccessor(): string
    {
        return 'setting.manager';
    }
}
