<?php

namespace App\Support;

final class DecimalDisplay
{
    public static function normalize(string|int|null $value, string $fallback = ''): string
    {
        if ($value === null) {
            return $fallback;
        }

        $decimal = trim((string) $value);

        if ($decimal === '') {
            return $fallback;
        }

        if (! preg_match('/^[+-]?\d+(?:\.\d+)?$/D', $decimal)) {
            return $decimal;
        }

        $sign = '';
        if ($decimal[0] === '-' || $decimal[0] === '+') {
            $sign = $decimal[0];
            $decimal = substr($decimal, 1);
        }

        [$integer, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');
        $fraction = rtrim($fraction, '0');

        if (trim($integer, '0') === '' && trim($fraction, '0') === '') {
            return '0';
        }

        return $sign.$integer.($fraction !== '' ? '.'.$fraction : '');
    }

    public static function localized(string|int|null $value, string $fallback = '—'): string
    {
        $decimal = self::normalize($value, $fallback);

        if (! preg_match('/^([+-]?)(\d+)(?:\.(\d+))?$/D', $decimal, $matches)) {
            return $decimal;
        }

        $integer = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $matches[2]);
        $fraction = $matches[3] ?? '';

        return $matches[1].$integer.($fraction !== '' ? ','.$fraction : '');
    }

    public static function isPositive(string|int|null $value): bool
    {
        $decimal = self::normalize($value);

        return preg_match('/^\+?(?:0*[1-9]\d*)(?:\.\d+)?$|^\+?0*\.\d*[1-9]\d*$/D', $decimal) === 1;
    }
}
