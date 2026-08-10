<?php

namespace App\Support;

class Csv
{
    public static function writeRow($stream, array $values): void
    {
        fputcsv($stream, array_map([self::class, 'safeCell'], $values));
    }

    public static function safeCell(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
