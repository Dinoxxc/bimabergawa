<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\SystemSettingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (SystemSetting $setting) {
            Cache::forget("system_setting_{$setting->key}");
            SystemSettingService::clearCache($setting->key);
        });

        static::deleted(function (SystemSetting $setting) {
            Cache::forget("system_setting_{$setting->key}");
            SystemSettingService::clearCache($setting->key);
        });
    }

    /**
     * Get setting value by key with cache and intelligent resolution.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return SystemSettingService::get($key, $default);
    }

    /**
     * Set setting value by key and clear cache.
     */
    public static function set(string $key, mixed $value, ?string $group = null, ?string $type = null): void
    {
        SystemSettingService::set($key, $value, $group, $type);
    }
}
