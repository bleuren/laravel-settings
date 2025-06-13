<?php

declare(strict_types=1);

namespace Bleuren\Setting\Console\Commands;

use Bleuren\Setting\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SettingClear extends Command
{
    protected $signature = 'setting:clear {key? : 特定設定鍵名，不提供則清除所有設定緩存}';

    protected $description = '清除設定緩存';

    public function handle(): int
    {
        $key = $this->argument('key');

        if ($key) {
            $this->clearSingleSetting($key);
        } else {
            $this->clearAllSettings();
        }

        return Command::SUCCESS;
    }

    /**
     * 清除單一設定的緩存
     *
     * @param  string  $key  設定鍵名
     */
    protected function clearSingleSetting(string $key): void
    {
        $cacheKey = Setting::cacheKey($key);
        Cache::forget($cacheKey);
        Setting::clearMemoryCache();

        $this->info("設定 '{$key}' 的緩存已清除。");
    }

    /**
     * 清除所有設定的緩存
     */
    protected function clearAllSettings(): void
    {
        $settingsCount = 0;
        $settings = Setting::all('key');

        foreach ($settings as $setting) {
            $cacheKey = Setting::cacheKey($setting->key);
            Cache::forget($cacheKey);
            $settingsCount++;
        }

        Setting::clearMemoryCache();
        $this->info("所有設定緩存已清除，共 {$settingsCount} 項設定。");
    }
}
