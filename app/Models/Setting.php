<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Once;

/**
 * An application-wide value set by the admins, by key.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    public static function get(string $key, mixed $default = null): mixed
    {
        // Read once per request: the home page asks for every organization.
        $values = once(fn () => static::pluck('value', 'key')->all());

        return $values[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        Once::flush();
    }
}
