<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

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
