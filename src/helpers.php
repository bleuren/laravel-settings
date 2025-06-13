<?php

declare(strict_types=1);

if (! function_exists('setting')) {
    /**
     * 獲取設定值
     *
     * @param  string  $key  設定鍵名
     * @param  mixed  $default  默認值
     * @return mixed 設定值或默認值
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return \Bleuren\LaravelSetting\Facades\Setting::get($key, $default);
    }
}
