<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Bleuren\LaravelSetting\Traits\HasSettings;
use Illuminate\Database\Eloquent\Model;

class CustomSetting extends Model
{
    use HasSettings;

    protected $table = 'custom_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
        'category',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getSettingsByCategory(string $category)
    {
        return $this->where('category', $category)->get();
    }

    public function getPublicSettings()
    {
        return $this->where('is_public', true)->get();
    }
}
