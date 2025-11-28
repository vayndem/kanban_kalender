<?php

namespace App\Imports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class SiswaImport implements ToModel, WithStartRow, WithCustomCsvSettings
{
    // Konfigurasi untuk memastikan file CSV dibaca dengan delimiter titik koma (;)
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
        ];
    }

    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // Pengecekan dasar pada Kolom B
        if (!isset($row[1]) || empty($row[1])) {
            return null;
        }

        // MENGGUNAKAN firstOrCreate: Jika siswa sudah ada, data diabaikan (AMAN)
        return Siswa::firstOrCreate(
            ['name' => $row[1]], // Kriteria Pencarian (Nama Lengkap)
            [
                'panggilan' => $row[2] ?? null, // Kolom C
                'kelas'     => $row[4] ?? null, // Kolom E
            ]
        );
    }
}
