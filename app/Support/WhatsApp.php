<?php

namespace App\Support;

class WhatsApp
{
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62'.$digits;
        }

        if (! preg_match('/^62[1-9]\d{7,12}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public static function url(?string $phone): ?string
    {
        $number = self::normalize($phone);

        return $number ? "https://wa.me/{$number}" : null;
    }
}
