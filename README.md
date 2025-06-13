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
```

## 記憶化緩存

Laravel Settings套件在Laravel 12中利用記憶化緩存（Memoized Cache）功能來減少同一請求中的重複查詢，這可以進一步提高性能。

## 設定預載入

您可以在應用啟動時自動預載入常用設定，以避免在首次訪問時產生延遲：

1. 首先，在 `config/settings.php` 中啟用預載入：

```php
'eager_load' => true,
```

2. 然後指定需要預載入的設定鍵：

```php
'eager_load_keys' => [
    'site_name',
    'site_logo',
    'maintenance_mode',
],
```

## 自定義資料庫連接和表名

您可以在 `config/settings.php` 中自定義資料庫連接和表名：

```php
// 自定義資料庫連接（可選）
'database_connection' => 'mysql',

// 自定義表名（預設為 'settings'）
'table' => 'app_settings',
```

## 貢獻

非常歡迎您對Laravel Settings套件的貢獻。請隨時提交任何問題或拉取請求。

## 授權

本Laravel Settings套件是根據[MIT許可證](http://opensource.org/licenses/MIT)授權的開源軟體。