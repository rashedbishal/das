<?php

namespace App\Support;

class PhoneNumber
{
    public static function normalizeBangladeshi(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (str_starts_with($digits, '880')) {
            $digits = '0'.substr($digits, 3);
        } elseif (! str_starts_with($digits, '0')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function isValidBangladeshi(?string $normalized): bool
    {
        return $normalized !== null && preg_match('/^01[3-9]\d{8}$/', $normalized) === 1;
    }
}
