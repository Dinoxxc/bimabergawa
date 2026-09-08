<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
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

    /**
     * Get setting value by key with cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting !== null ? $setting->value : $default;
        });
    }

    /**
     * Set setting value by key and clear cache.
     */
    public static function set(string $key, mixed $value, ?string $group = null, ?string $type = null): void
    {
        $data = ['value' => (string) $value];
        if ($group !== null) {
            $data['group'] = $group;
        }
        if ($type !== null) {
            $data['type'] = $type;
        }

        static::updateOrCreate(['key' => $key], $data);
        Cache::forget("system_setting_{$key}");
    }
}
