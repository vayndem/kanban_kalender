<?php

namespace App\Exports;

use App\Exports\Concerns\MemaksaTeksUntukAwalanPlus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaTemplateExport implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithStyles, WithTitle
{
    use MemaksaTeksUntukAwalanPlus;

    public function title(): string
    {
        return 'Kerangka Import Siswa';
    }

    public function headings(): array
    {
        return ['Nama Lengkap', 'Panggilan', 'Kelas', 'No. HP', 'Nama Paket'];
    }

    public function array(): array
    {
        return [
            ['Contoh Siswa Satu', 'Satu', '7A', '081234567890', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
            ],
        ];
    }
}
