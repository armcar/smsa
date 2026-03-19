<?php

namespace App\Support;

final class Nif
{
    public static function isValid(?string $value): bool
    {
        $nif = preg_replace('/\D+/', '', (string) $value);

        if (strlen($nif) !== 9) {
            return false;
        }

        $digits = array_map('intval', str_split($nif));

        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += $digits[$i] * (9 - $i);
        }

        $check = 11 - ($sum % 11);
        if ($check >= 10) $check = 0;

        return $digits[8] === $check;
    }
}