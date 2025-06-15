<?php

declare(strict_types=1);

describe('Basic Package Test', function () {

    it('can create setting table', function () {
        expect(\Illuminate\Support\Facades\Schema::hasTable('settings'))->toBeTrue();
    });

    it('can create custom setting table', function () {
        expect(\Illuminate\Support\Facades\Schema::hasTable('custom_settings'))->toBeTrue();
    });

    it('has required classes available', function () {
        expect(class_exists(\Bleuren\LaravelSetting\Setting::class))->toBeTrue();
        expect(class_exists(\Bleuren\LaravelSetting\SettingManager::class))->toBeTrue();
        expect(class_exists(\Bleuren\LaravelSetting\Facades\Setting::class))->toBeTrue();
        expect(trait_exists(\Bleuren\LaravelSetting\Traits\HasSettings::class))->toBeTrue();
    });

    it('has helper function available', function () {
        expect(function_exists('setting'))->toBeTrue();
    });

    it('can instantiate basic components', function () {
        $setting = new \Bleuren\LaravelSetting\Setting;
        $manager = new \Bleuren\LaravelSetting\SettingManager;

        expect($setting)->toBeInstanceOf(\Bleuren\LaravelSetting\Setting::class);
        expect($manager)->toBeInstanceOf(\Bleuren\LaravelSetting\SettingManager::class);
    });
});
