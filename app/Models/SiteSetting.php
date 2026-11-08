<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Retrieve a site setting by key or return default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $all = static::getAll();
        return $all[$key] ?? $default;
    }

    /**
     * Set or update a site setting by key.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('site_settings_all');
        Cache::forget('site_settings_shared_payload');
    }

    /**
     * Get all site settings as a key-value associative array.
     */
    public static function getAll(): array
    {
        return Cache::remember('site_settings_all', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });
    }
}


