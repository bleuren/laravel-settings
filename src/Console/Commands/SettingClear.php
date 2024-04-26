<?php

declare(strict_types=1);

namespace Bleuren\Setting\Console\Commands;

use Bleuren\Setting\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SettingClear extends Command
{
    protected $signature = 'setting:clear';

    protected $description = 'Clears all cache for the settings';

    public function handle()
    {
        $settings = Setting::all('key');
        foreach ($settings as $setting) {
            $cacheKey = Setting::cacheKey($setting->key);
            Cache::forget($cacheKey);
        }

        $this->info('All settings cache has been cleared.');

        return 0;
    }
}
