# Laravel Settings Package

本套件提供了一個簡單的方式，用於在Laravel應用程式中透過資料庫管理應用設定。使用此套件，您可以讀取和設置專案特定參數，並在程式碼中使用它們，同時還能利用Laravel的緩存功能來優化這些設定的存取和修改。

## 功能特點

- 在資料庫中存儲設定，每個設定都有唯一的鍵值。
- 自動緩存設定以提高性能。
- 使用記憶化緩存減少同一請求中的重複查詢。
- 支援批量設定操作。
- 可自定義資料庫連接和表名。
- 提供命令行工具用於清除設定緩存。
- 支持預載入常用設定以提高性能。
- 支援使用自定義 Eloquent 模型來處理設定。
- 提供 HasSettings trait 以增強模型功能。
- 完全向後兼容，無需修改現有代碼。

## 安裝

使用以下命令在Laravel專案中安裝套件：

```bash
composer require bleuren/laravel-settings
```

## 設定

安裝後，發布遷移文件和配置文件：

```bash
php artisan vendor:publish --tag=laravel-settings-migrations
php artisan vendor:publish --tag=laravel-settings-config
```

運行遷移以創建 `settings` 表：

```bash
php artisan migrate
```

## 使用方法

### 獲取設定

您可以使用 `Setting` 門面來獲取設定值。以下是獲取設定值的範例：

```php
use Bleuren\Setting\Facades\Setting;

// 獲取設定值，如果不存在則返回預設值
$value = Setting::get('some_key', 'default_value');
```

### 設置設定值

要更新或創建新的設定：

```php
use Bleuren\Setting\Facades\Setting;

// 設置單個設定值
Setting::set('some_key', 'new_value', '選項描述');

// 批量設置設定值
Setting::setMany([
    'app_name' => 'My App',
    'app_logo' => '/images/logo.png',
    'maintenance_mode' => false
], '應用設定');
```

### 檢查設定是否存在

檢查某個設定是否存在：

```php
if (Setting::has('some_key')) {
    // 做某些操作
}
```

### 刪除設定

刪除某個設定：

```php
Setting::remove('some_key');
```

### 清除緩存

如果您需要清除設定的緩存，可以使用提供的命令：

```bash
# 清除所有設定的緩存
php artisan setting:clear

# 清除特定設定的緩存
php artisan setting:clear some_key

# 清除自定義模型的緩存
php artisan setting:clear some_key --model="App\Models\CustomSetting"
```

## 高級功能

### 記憶化緩存

套件自動利用 Laravel 12 的記憶化緩存功能，減少同一請求中的重複查詢。

### 預載入設定

配置常用設定的預載入以提升性能：

```php
// config/settings.php
'eager_load' => true,
'eager_load_keys' => [
    'app.name',
    'app.theme', 
    'user.default_timezone',
],
```

## 自定義設定模型

Laravel Settings 套件採用靈活的架構設計，您可以使用自己的 Eloquent 模型來處理設定，而不是僅限於套件預設的 Setting 模型。

### 使用 HasSettings Trait

1. **創建自定義設定模型**：

```php
<?php

namespace App\Models;

use Bleuren\LaravelSetting\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    use HasSettings;

    protected $table = 'user_settings';
    
    protected $fillable = [
        'key', 'value', 'description',
        'user_id', 'category', 'is_public', // 額外的自定義欄位
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'user_id' => 'integer',
    ];

    // 關聯關係
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 自定義方法
    public function getSettingsByCategory(string $category)
    {
        return $this->where('category', $category)->get();
    }

    public function getUserSettings(int $userId)
    {
        return $this->where('user_id', $userId)->get();
    }
}
```

2. **創建資料表遷移**：

```bash
php artisan make:migration create_user_settings_table
```

```php
Schema::create('user_settings', function (Blueprint $table) {
    $table->id();
    $table->string('key', 191)->index();
    $table->text('value')->nullable();
    $table->string('description')->nullable();
    
    // 自定義欄位
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('category')->default('general')->index();
    $table->boolean('is_public')->default(false);
    
    $table->timestamps();
    
    // 複合唯一鍵（用戶級別的設定唯一性）
    $table->unique(['user_id', 'key']);
});
```

3. **配置自定義模型**：

```php
// config/settings.php
'model' => App\Models\UserSetting::class,
```

### 完整功能支援

自定義模型支援所有原有功能，包括：

```php
use Bleuren\Setting\Facades\Setting;

// 透過 Facade 使用（推薦）
$value = Setting::get('theme', 'dark');
Setting::set('theme', 'light', '用戶主題設定');
Setting::setMany(['lang' => 'zh-TW', 'timezone' => 'Asia/Taipei']);

// 直接使用模型靜態方法
UserSetting::set('notification', true);
$hasNotification = UserSetting::has('notification');

// 使用模型實例進行複雜操作
$model = Setting::getModel();
$userSettings = $model->getUserSettings(1);
```

### HasSettings Trait API

| 方法 | 描述 | 靜態支援 |
|------|------|----------|
| `getSetting($key, $default)` | 獲取設定值 | ✅ `get()` |
| `setSetting($key, $value, $desc)` | 設置設定值 | ✅ `set()` |
| `setManySettings($settings, $desc)` | 批量設置 | ✅ `setMany()` |
| `hasSetting($key)` | 檢查設定存在 | ✅ `has()` |
| `removeSetting($key)` | 刪除設定 | ✅ `remove()` |
| `getAllSettings()` | 獲取所有設定 | ✅ `all()` |
| `searchSettings($pattern)` | 搜索設定 | ✅ `search()` |
| `clearMemoryCache()` | 清除緩存 | ✅ |

## 自定義資料庫連接和表名

您可以在 `config/settings.php` 中自定義資料庫連接和表名：

```php
// 指定自定義模型（可選，預設使用套件的 Setting 模型）
'model' => \Bleuren\LaravelSetting\Setting::class,

// 自定義資料庫連接（可選）
'database_connection' => 'mysql',

// 自定義表名（預設為 'settings'）
'table' => 'app_settings',
```

## 配置與最佳實踐

### 完整配置選項

```php
// config/settings.php
return [
    'cache_prefix' => 'settings.',                    // 緩存前綴
    'model' => \Bleuren\LaravelSetting\Setting::class, // 設定模型
    'eager_load' => false,                            // 預載入開關
    'eager_load_keys' => [],                          // 預載入鍵
    'database_connection' => null,                    // 資料庫連接
    'table' => 'settings',                           // 表名
];
```

### 環境變數

```env
SETTINGS_MODEL="App\Models\UserSetting"
SETTINGS_EAGER_LOAD=true
SETTINGS_DB_CONNECTION=mysql
```

### 最佳實踐

1. **命名規範**：使用點號分隔，如 `app.name`、`user.theme`
2. **模型設計**：為不同用途設計專用模型（用戶設定、系統設定等）
3. **緩存策略**：合理使用預載入，避免過多的記憶體佔用
4. **資料驗證**：在設定前進行適當的資料驗證

### 常見問題

- **模型錯誤**：確保自定義模型使用 `HasSettings` trait
- **緩存問題**：使用 `php artisan setting:clear` 清除緩存
- **遷移問題**：確保資料表包含 `key`、`value`、`description` 欄位

## 貢獻

非常歡迎您對Laravel Settings套件的貢獻。請隨時提交任何問題或拉取請求。

## 授權

本Laravel Settings套件是根據[MIT許可證](http://opensource.org/licenses/MIT)授權的開源軟體。