<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** @var array<string, mixed> */
    protected static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::where('key', $key)->value('value');
        }

        return static::$cache[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache[$key] = $value;
    }
}
