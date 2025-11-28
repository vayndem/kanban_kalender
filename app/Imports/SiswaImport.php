<?php

namespace App\Imports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SiswaImport implements ToModel, WithStartRow
{
    /**
     * Tentukan mulai baris ke berapa data dibaca.
     * Karena baris 1 adalah Header (Timestamp, Nama Lengkap, dll), kita mulai dari baris 2.
     */
    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Validasi sederhana: Jika kolom B (index 1) kosong, jangan import baris ini
        if (!isset($row[1])) {
            return null;
        }

        /*
         * MAPPING KOLOM:
         * $row[0] = Timestamp (Kolom A)
         * $row[1] = Nama Lengkap (Kolom B) -> Kita ambil ini
         * $row[2] = Nama Panggilan (Kolom C)
         */

        return new Siswa([
            'name' => $row[1],
        ]);
    }
}
