<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting;

use Bleuren\LaravelSetting\Contracts\SettingRepository;
use Bleuren\LaravelSetting\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * SettingManager 類別
 *
 * 管理不同的設定模型，提供統一的設定操作介面
 * 實現 SettingRepository Contract
 */
class SettingManager implements SettingRepository
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

        if (! $this->modelUsesHasSettings($model)) {
            throw new \InvalidArgumentException('設定模型必須使用 HasSettings trait');
        }

        return $model;
    }

    /**
     * 檢查模型是否使用 HasSettings trait
     */
    protected function modelUsesHasSettings(Model $model): bool
    {
        return in_array(HasSettings::class, class_uses_recursive($model), true);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->model->getSetting($key, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, ?string $description = null): Model
    {
        return $this->model->setSetting($key, $value, $description);
    }

    /**
     * {@inheritdoc}
     */
    public function setMany(array $settings, ?string $description = null): Collection
    {
        return $this->model->setManySettings($settings, $description);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        return $this->model->hasSetting($key);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): bool
    {
        return $this->model->removeSetting($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clearMemoryCache(): void
    {
        $this->model->clearMemoryCache();
    }

    /**
     * {@inheritdoc}
     */
    public function cacheKey(string $key): string
    {
        return $this->model->getCacheKey($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->model->getAllSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $pattern): Collection
    {
        return $this->model->searchSettings($pattern);
    }

    /**
     * 動態調用模型方法
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->model->$method(...$parameters);
    }
}
