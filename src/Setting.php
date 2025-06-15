<?php

declare(strict_types=1);

namespace Bleuren\LaravelSetting;

use Bleuren\LaravelSetting\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Setting extends Model
{
    use HasSettings;

    protected $fillable = [
        'key', 'description', 'value',
    ];

    /**
     * 創建一個新的實例
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
     * 重寫 trait 中的緩存鍵生成方法以保持簡潔的緩存鍵格式
     * 對於默認的 Setting 模型，我們不需要包含類名
     */
    public function getCacheKey(string $key): string
    {
        $prefix = Config::get('settings.cache_prefix', 'settings.');

        return $prefix.$key;
    }
}
