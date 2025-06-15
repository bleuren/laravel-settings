<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Setting Repository Contract
 *
 * 定義設定操作的標準介面，遵循 Laravel Contract 模式
 */
interface SettingRepository
{
    /**
     * 獲取設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $default  默認值
     * @return mixed 設定值或默認值
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * 設置設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $value  設定值
     * @param  string|null  $description  描述
     * @return Model 設定模型實例
     */
    public function set(string $key, mixed $value, ?string $description = null): Model;

    /**
     * 批量設置設定值
     *
     * @param  array<string, mixed>  $settings  設定數組
     * @param  string|null  $description  描述
     * @return Collection<int, Model> 設定模型實例集合
     */
    public function setMany(array $settings, ?string $description = null): Collection;

    /**
     * 檢查設定是否存在
     *
     * @param  string  $key  設定鍵名
     * @return bool 設定是否存在
     */
    public function has(string $key): bool;

    /**
     * 刪除設定
     *
     * @param  string  $key  設定鍵名
     * @return bool 刪除是否成功
     */
    public function remove(string $key): bool;

    /**
     * 獲取所有設定
     *
     * @return Collection<int, Model>
     */
    public function all(): Collection;

    /**
     * 根據鍵名模式搜索設定
     *
     * @param  string  $pattern  搜索模式
     * @return Collection<int, Model>
     */
    public function search(string $pattern): Collection;

    /**
     * 清除記憶化緩存
     */
    public function clearMemoryCache(): void;

    /**
     * 獲取設定的完整緩存鍵名
     *
     * @param  string  $key  設定鍵名
     * @return string 完整緩存鍵名
     */
    public function cacheKey(string $key): string;

    /**
     * 獲取模型實例
     */
    public function getModel(): Model;
}
