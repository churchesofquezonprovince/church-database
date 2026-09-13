<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeveloperSetting extends Model
{
    public const KEY_MEETING_FORM_AUTOFILL_MATCHES =
        'meeting_form_autofill_matches';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function string(
        string $key,
        ?string $default = null
    ): ?string {
        $value =
            static::query()
                ->where('key', $key)
                ->value('value');

        return $value !== null
            ? (string) $value
            : $default;
    }

    public static function integer(
        string $key,
        int $default = 0
    ): int {
        $value =
            static::string(
                $key
            );

        if (
            $value === null
            || ! is_numeric($value)
        ) {
            return $default;
        }

        return (int) $value;
    }

    public static function putValue(
        string $key,
        mixed $value
    ): void {
        static::query()->updateOrCreate(
            [
                'key' =>
                    $key,
            ],
            [
                'value' =>
                    $value === null
                        ? null
                        : (string) $value,
            ]
        );
    }
}
