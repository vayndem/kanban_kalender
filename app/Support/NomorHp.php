<?php

namespace App\Support;

class NomorHp
{
    public static function normalkan(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $bersih = preg_replace('/[^0-9+]/', '', trim($nilai));

        if ($bersih === '' || $bersih === null) {
            return null;
        }

        return match (true) {
            str_starts_with($bersih, '+62') => $bersih,
            str_starts_with($bersih, '62') => '+'.$bersih,
            str_starts_with($bersih, '0') => '+62'.substr($bersih, 1),
            str_starts_with($bersih, '8') => '+62'.$bersih,
            default => null,
        };
    }
}
