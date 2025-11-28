<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\SiswaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportSiswa extends Command
{
    protected $signature = 'siswa:import';

    protected $description = 'Import data siswa dari file Excel/CSV, termasuk panggilan dan kelas.';

    public function handle()
    {
        // Pastikan nama file sesuai dengan yang kamu simpan di storage/app/
        $fileName = '1.xlsx';

        $filePath = storage_path('app/' . $fileName);

        // Menggunakan file_exists untuk keandalan path absolut
        if (!file_exists($filePath)) {
            // Jika file tidak ditemukan, coba cari dengan ekstensi CSV sebagai alternatif
            $csvFileName = '1.csv';
            $csvFilePath = storage_path('app/' . $csvFileName);

            if (file_exists($csvFilePath)) {
                $filePath = $csvFilePath;
                $fileName = $csvFileName;
            } else {
                $this->error("FILE TIDAK DITEMUKAN PADA PATH: {$filePath}");
                $this->info("Pastikan file '{$fileName}' ada di dalam folder 'storage/app/' proyek Anda.");
                return 1;
            }
        }

        $this->info("Sedang memproses import data siswa dari {$fileName}...");

        try {
            // Proses Import menggunakan path absolut
            Excel::import(new SiswaImport, $filePath);

            $this->info('Berhasil! Data siswa (Nama, Panggilan, Kelas) telah diimport.');

        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
        }

        return 0;
    }
}
