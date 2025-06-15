# Laravel Settings

**一個現代化、高性能的 Laravel 設定管理套件**

*透過資料庫存儲應用設定，支援智能緩存、自定義模型和依賴注入*

---

## ✨ 功能特點

- 🗄️ **資料庫存儲** - 在資料庫中安全存儲設定，支援複雜資料類型
- ⚡ **智能緩存** - 多層緩存策略：Laravel 緩存 + 記憶化緩存
- 🔧 **Contract 驅動** - 基於 `SettingRepository` Contract，支援依賴注入
- 🎯 **自定義模型** - 完全支援自定義 Eloquent 模型和欄位
- 📦 **批量操作** - 高效的批量設定操作，支援資料庫事務
- 🚀 **預載入功能** - 可配置的常用設定預載入，提升性能
- 🛠️ **命令行工具** - 提供緩存管理和維護命令
- 🧪 **完整測試** - 106 個測試，229 個斷言，確保穩定性
- 📋 **Laravel 慣例** - 完全符合 Laravel 設計模式和最佳實踐

## 📋 系統需求

- PHP 8.3+
- Laravel 11.0+ 或 12.0+

## 🚀 安裝

透過 Composer 安裝套件：

```bash
composer require bleuren/laravel-settings
```

發布並執行遷移：

```bash
php artisan vendor:publish --tag=laravel-settings-migrations
php artisan migrate
```

（可選）發布配置文件：

```bash
php artisan vendor:publish --tag=laravel-settings-config
```

## 📖 基本使用

### Facade 方式（推薦）

```php
use Bleuren\LaravelSetting\Facades\Setting;

// 獲取設定值
$appName = Setting::get('app.name', 'Default App');

// 設置設定值
Setting::set('app.name', 'My Application', '應用程式名稱');

// 批量設置
Setting::setMany([
    'app.name' => 'My App',
    'app.theme' => 'dark',
    'app.timezone' => 'Asia/Taipei',
    'maintenance.mode' => false
], '應用程式基本設定');

// 檢查設定是否存在
if (Setting::has('app.name')) {
    // 執行相關邏輯
}

// 刪除設定
Setting::remove('old.setting');

// 搜索設定
$appSettings = Setting::search('app.%');

// 獲取所有設定
$allSettings = Setting::all();
```

### 輔助函數

```php
// 簡潔的獲取方式
$appName = setting('app.name', 'Default App');
$theme = setting('app.theme');
```

### 依賴注入方式

```php
use Bleuren\LaravelSetting\Contracts\SettingRepository;

class UserController extends Controller
{
    public function __construct(
        private SettingRepository $settings
    ) {}

    public function updateProfile(Request $request)
    {
        // 使用注入的設定服務
        $defaultTheme = $this->settings->get('user.default_theme', 'light');
        
        // 更新使用者偏好設定
        $this->settings->setMany([
            'user.theme' => $request->theme,
            'user.language' => $request->language,
        ], '使用者偏好設定');
    }
}
```

## 🎨 自定義模型

### 創建自定義設定模型

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
        'user_id', 'category', 'is_public'
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // 自定義關聯
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 自定義查詢方法
    public function getSettingsByCategory(string $category)
    {
        return $this->where('category', $category)->get();
    }

    public function getPublicSettings()
    {
        return $this->where('is_public', true)->get();
    }
}
```

### 配置自定義模型

```php
// config/settings.php
return [
    'model' => App\Models\UserSetting::class,
    'table' => 'user_settings',
    'database_connection' => 'mysql',
    'cache_prefix' => 'user_settings.',
    
    // 其他配置...
];
```

### 使用自定義模型

```php
// 透過 Facade（會使用配置的模型）
Setting::set('notification.email', true);

// 直接使用模型靜態方法
UserSetting::set('theme.color', 'blue');
$hasNotification = UserSetting::has('notification.email');

// 使用自定義方法
$publicSettings = UserSetting::getPublicSettings();
$categorySettings = UserSetting::getSettingsByCategory('appearance');
```

## 📚 API 參考

### SettingRepository Contract

| 方法 | 描述 | 返回類型 |
|------|------|----------|
| `get(string $key, mixed $default = null)` | 獲取設定值 | `mixed` |
| `set(string $key, mixed $value, ?string $description = null)` | 設置設定值 | `Model` |
| `setMany(array $settings, ?string $description = null)` | 批量設置設定 | `Collection` |
| `has(string $key)` | 檢查設定是否存在 | `bool` |
| `remove(string $key)` | 刪除設定 | `bool` |
| `all()` | 獲取所有設定 | `Collection` |
| `search(string $pattern)` | 搜索設定（支援 SQL LIKE） | `Collection` |
| `clearMemoryCache()` | 清除記憶體緩存 | `void` |
| `cacheKey(string $key)` | 獲取緩存鍵名 | `string` |
| `getModel()` | 獲取模型實例 | `Model` |

### HasSettings Trait 方法

| Trait 方法 | 靜態別名 | 描述 |
|------------|----------|------|
| `getSetting($key, $default)` | `get()` | 獲取設定值 |
| `setSetting($key, $value, $desc)` | `set()` | 設置設定值 |
| `setManySettings($settings, $desc)` | `setMany()` | 批量設置 |
| `hasSetting($key)` | `has()` | 檢查設定存在 |
| `removeSetting($key)` | `remove()` | 刪除設定 |
| `getAllSettings()` | `all()` | 獲取所有設定 |
| `searchSettings($pattern)` | `search()` | 搜索設定 |

## ⚙️ 配置選項

```php
// config/settings.php
return [
    // 設定模型類別
    'model' => env('SETTINGS_MODEL', \Bleuren\LaravelSetting\Setting::class),

    // 資料庫配置
    'database_connection' => env('SETTINGS_DB_CONNECTION', null),
    'table' => env('SETTINGS_TABLE', 'settings'),

    // 緩存配置
    'cache_prefix' => env('SETTINGS_CACHE_PREFIX', 'settings.'),

    // 預載入配置
    'eager_load' => env('SETTINGS_EAGER_LOAD', false),
    'eager_load_keys' => [
        'app.name',
        'app.theme',
        'app.timezone',
        // 添加常用的設定鍵...
    ],

    // 效能配置
    'batch_size' => env('SETTINGS_BATCH_SIZE', 100),
    'enable_query_log' => env('SETTINGS_ENABLE_QUERY_LOG', false),
];
```

## 🚀 高級功能

### 預載入設定

提升應用啟動性能，預載入常用設定：

```php
// config/settings.php
'eager_load' => true,
'eager_load_keys' => [
    'app.name',
    'app.logo',
    'app.theme',
    'mail.from_address',
    'social.facebook_url',
],
```

### 緩存管理

```bash
# 清除所有設定緩存
php artisan setting:clear

# 清除特定設定緩存
php artisan setting:clear app.name

# 清除記憶體緩存（程式碼中）
Setting::clearMemoryCache();
```

### 批量操作最佳實踐

```php
// 高效的批量操作
$settings = [
    'mail.driver' => 'smtp',
    'mail.host' => 'smtp.gmail.com',
    'mail.port' => 587,
    'mail.encryption' => 'tls',
];

// 使用事務確保一致性
Setting::setMany($settings, '郵件服務設定');
```

### 搜索和過濾

```php
// 搜索所有應用相關設定
$appSettings = Setting::search('app.%');

// 搜索所有郵件設定
$mailSettings = Setting::search('mail.%');

// 使用自定義模型的進階搜索
$publicSettings = UserSetting::getPublicSettings();
$categorySettings = UserSetting::getSettingsByCategory('appearance');
```

## 🏗️ 架構設計

### 設計模式

- **Contract Pattern** - 基於 `SettingRepository` 介面
- **Repository Pattern** - 抽象資料存取層
- **Service Provider Pattern** - Laravel 服務註冊
- **Facade Pattern** - 簡潔的靜態介面
- **Trait Pattern** - 可重用的功能模組

### 緩存策略

1. **Laravel 緩存** - 使用 `Cache::rememberForever()` 永久緩存
2. **記憶化緩存** - 請求期間的記憶體緩存
3. **模型隔離** - 不同模型使用獨立緩存空間
4. **智能失效** - 資料更新時自動清除相關緩存

### 依賴注入

```php
// 在服務提供者中註冊
$this->app->bind(SettingRepository::class, SettingManager::class);

// 在控制器中使用
public function __construct(SettingRepository $settings) {
    $this->settings = $settings;
}
```

## 🧪 測試

執行完整測試套件：

```bash
# 執行所有測試
composer test

# 執行測試並生成覆蓋率報告
composer test-coverage

# 執行特定測試
./vendor/bin/pest tests/Feature/SettingFacadeTest.php
```

### 測試覆蓋範圍

- ✅ **106 個測試，229 個斷言**
- ✅ Contract 和依賴注入測試
- ✅ 緩存機制測試
- ✅ 自定義模型測試
- ✅ 批量操作測試
- ✅ 錯誤處理測試
- ✅ 整合測試

## 🔧 遷移指南

### 從其他設定套件遷移

如果您正在使用其他設定套件，可以輕鬆遷移：

```php
// 舊的設定套件
Settings::set('key', 'value');
$value = Settings::get('key');

// Laravel Settings（相容的 API）
Setting::set('key', 'value');
$value = Setting::get('key');
```

### 資料庫結構

預設的設定表結構：

```sql
CREATE TABLE settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(191) UNIQUE NOT NULL,
    value TEXT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_settings_key (key)
);
```

## 📄 授權

本套件基於 [MIT 授權條款](LICENSE.md) 開源。