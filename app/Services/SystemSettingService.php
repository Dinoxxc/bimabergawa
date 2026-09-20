<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get a setting value with intelligent key resolution and caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "system_setting_service_{$key}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            // 1. Direct key match (e.g., 'commercial_tax.tax_vat_rate')
            $setting = SystemSetting::where('key', $key)->first();
            if ($setting !== null) {
                return self::castValue($setting->value, $setting->type);
            }

            // 2. Dot-fallback match if key is shorthand without group (e.g., 'tax_vat_rate')
            if (! str_contains($key, '.')) {
                $settingWithGroup = SystemSetting::where('key', 'like', "%.{$key}")->first();
                if ($settingWithGroup !== null) {
                    return self::castValue($settingWithGroup->value, $settingWithGroup->type);
                }
            }

            return $default;
        });
    }

    /**
     * Get a float setting value.
     */
    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = self::get($key, $default);

        return is_numeric($value) ? (float) $value : $default;
    }

    /**
     * Get an integer setting value.
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Get a string setting value.
     */
    public static function getString(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        return $value !== null ? (string) $value : $default;
    }

    /**
     * Get a boolean setting value.
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return $default;
    }

    /**
     * Get a JSON setting decoded as an array.
     */
    public static function getJson(string $key, array $default = []): array
    {
        $value = self::get($key, $default);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : $default;
        }

        return $default;
    }

    /**
     * Set a setting value and clear relevant caches.
     */
    public static function set(string $key, mixed $value, ?string $group = null, ?string $type = null): void
    {
        $stringValue = is_array($value) ? json_encode($value) : (string) $value;
        $inferredType = $type ?? (is_array($value) ? 'json' : (is_numeric($value) ? 'decimal' : 'string'));

        $data = [
            'value' => $stringValue,
            'type' => $inferredType,
        ];

        if ($group !== null) {
            $data['group'] = $group;
        }

        SystemSetting::updateOrCreate(['key' => $key], $data);

        self::clearCache($key);
    }

    /**
     * Clear setting caches for given key.
     */
    public static function clearCache(string $key): void
    {
        Cache::forget("system_setting_service_{$key}");
        Cache::forget("system_setting_{$key}");

        if (str_contains($key, '.')) {
            $shortKey = substr($key, strrpos($key, '.') + 1);
            Cache::forget("system_setting_service_{$shortKey}");
            Cache::forget("system_setting_{$shortKey}");
        }
    }

    /**
     * Cast raw database value based on setting type.
     */
    private static function castValue(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'decimal', 'float' => (float) $value,
            'boolean', 'bool' => in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true),
            'json' => is_string($value) ? (json_decode($value, true) ?? []) : (array) $value,
            default => (string) $value,
        };
    }
}
