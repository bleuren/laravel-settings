<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting;

use Bleuren\LaravelSetting\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * SettingManager 類別
 *
 * 管理不同的設定模型，提供統一的設定操作介面
 */
class SettingManager
{
    /**
     * 設定模型實例
     */
    protected Model $model;

    /**
     * 創建設定管理器實例
     */
    public function __construct()
    {
        $this->model = $this->createModelInstance();
    }

    /**
     * 創建模型實例
     */
    protected function createModelInstance(): Model
    {
        $modelClass = Config::get('settings.model', Setting::class);

        if (! class_exists($modelClass)) {
            throw new \InvalidArgumentException("設定模型類別 [{$modelClass}] 不存在");
        }

        $model = new $modelClass;

        if (! $model instanceof Model) {
            throw new \InvalidArgumentException('設定模型必須繼承 Illuminate\\Database\\Eloquent\\Model');
        }

        // 檢查模型是否使用了 HasSettings trait
        if (! $this->modelHasSettingsFeature($model)) {
            throw new \InvalidArgumentException('設定模型必須使用 HasSettings trait');
        }

        return $model;
    }

    /**
     * 檢查模型是否具有設定功能
     */
    protected function modelHasSettingsFeature(Model $model): bool
    {
        $traits = class_uses_recursive($model);

        return in_array(HasSettings::class, $traits, true);
    }

    /**
     * 獲取設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $default  默認值
     * @return mixed 設定值或默認值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->model->getSetting($key, $default);
    }

    /**
     * 設置設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $value  設定值
     * @param  string|null  $description  描述
     * @return Model 設定模型實例
     */
    public function set(string $key, mixed $value, ?string $description = null): Model
    {
        return $this->model->setSetting($key, $value, $description);
    }

    /**
     * 批量設置設定值
     *
     * @param  array<string, mixed>  $settings  設定數組，格式為 ['key' => 'value', ...]
     * @param  string|null  $description  所有設定的預設描述
     * @return Collection<int, Model> 設定模型實例集合
     */
    public function setMany(array $settings, ?string $description = null): Collection
    {
        return $this->model->setManySettings($settings, $description);
    }

    /**
     * 檢查設定是否存在
     *
     * @param  string  $key  設定鍵名
     * @return bool 設定是否存在
     */
    public function has(string $key): bool
    {
        return $this->model->hasSetting($key);
    }

    /**
     * 刪除設定
     *
     * @param  string  $key  設定鍵名
     * @return bool 刪除是否成功
     */
    public function remove(string $key): bool
    {
        return $this->model->removeSetting($key);
    }

    /**
     * 清除記憶化緩存
     */
    public function clearMemoryCache(): void
    {
        $this->model->clearMemoryCache();
    }

    /**
     * 獲取設定的完整緩存鍵名
     *
     * @param  string  $key  設定的鍵名
     * @return string 完整緩存鍵名
     */
    public function cacheKey(string $key): string
    {
        return $this->model->getCacheKey($key);
    }

    /**
     * 獲取模型實例
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * 獲取所有設定
     *
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->model->getAllSettings();
    }

    /**
     * 根據鍵名模式搜索設定
     *
     * @param  string  $pattern  搜索模式
     * @return Collection<int, Model>
     */
    public function search(string $pattern): Collection
    {
        return $this->model->searchSettings($pattern);
    }

    /**
     * 動態調用模型方法
     * 這允許直接在SettingManager上調用模型的任何方法
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->model->$method(...$parameters);
    }
}
