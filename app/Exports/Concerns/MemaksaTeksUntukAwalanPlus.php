<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Tanpa ini, Excel/PhpSpreadsheet melihat "+6281234567890" atau "081234567890"
 * sebagai angka -- tanda "+"/nol di depan hilang dan nomor panjang berubah
 * jadi notasi ilmiah (6,2851E+12). Paksa string yang berbentuk nomor telepon
 * (berawalan "+" atau "0") tetap teks apa adanya.
 */
trait MemaksaTeksUntukAwalanPlus
{
    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && (str_starts_with($value, '+') || str_starts_with($value, '0'))) {
            return $cell->setValueExplicit($value, DataType::TYPE_STRING);
        }

        return (new DefaultValueBinder)->bindValue($cell, $value);
    }
}
