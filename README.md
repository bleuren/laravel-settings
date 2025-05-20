# Laravel Settings Package

This package provides an easy way to manage application settings via a database in Laravel applications. With this package, you can read and set project-specific parameters and use them within your code, leveraging the power of Laravel's caching to optimize access and modification of these settings.

## Features

- Store settings in a database with a unique key for each setting.
- Automatically cache settings to improve performance.
- Easy retrieval and updating of settings via a simple API.
- Commands included for clearing settings cache.

## Installation

To install the package, run the following command in your Laravel project:

```bash
composer require bleuren/laravel-settings
```

## Configuration

After installation, publish the migration file with the following command:

```bash
php artisan vendor:publish --tag=laravel-settings
```

Run the migrations to create the `settings` table:

```bash
php artisan migrate
```

## Usage

### Getting a Setting

You can retrieve settings using the `Setting` facade. Here's an example of how to get a setting value:

```php
$value = Setting::get('some_key', 'default_value');
```

### Setting a Value

To update or create a new setting:

```php
Setting::set('some_key', 'new_value', 'Optional description');
```

### Clearing Cache

If you need to clear the cache for the settings, you can use the provided command:

```bash
php artisan setting:clear
```

This command will clear all cached settings.

## Contributing

Contributions are welcome, and thank you for your interest in contributing to the Laravel Settings package. Please feel free to submit any issues or pull requests.

## License

This Laravel Settings package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).