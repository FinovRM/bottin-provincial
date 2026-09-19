<?php

namespace App\Support;

class CellPhone
{
    /**
     * Strip everything but digits, ready to store.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value);

        return $digits === '' ? null : $digits;
    }

    /**
     * Present stored digits as "(xxx) xxx-xxxx".
     */
    public static function format(?string $value): ?string
    {
        if ($value === null || strlen($value) !== 10) {
            return $value;
        }

        return sprintf('(%s) %s-%s', substr($value, 0, 3), substr($value, 3, 3), substr($value, 6, 4));
    }
}
