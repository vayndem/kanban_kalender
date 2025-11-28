<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\SiswaImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ImportSiswa extends Command
{
    /**
     * Signature untuk menjalankan command di terminal.
     */
    protected $signature = 'siswa:import';

    /**
     * Deskripsi command.
     */
    protected $description = 'Import nama siswa dari file Excel/CSV (Kolom B)';

    /**
     * Eksekusi command.
     */
    public function handle()
    {
        // Nama file sesuai yang kamu upload
        $fileName = '.xlsx';

        // Pastikan file ada di folder storage/app
        if (!Storage::exists($fileName)) {
            $this->error("File tidak ditemukan di storage/app/{$fileName}");
            $this->info("Pastikan kamu meletakkan file di folder 'storage/app/'");
            return 1;
        }

        $this->info('Sedang memproses import data siswa...');

        try {
            // Proses Import
            Excel::import(new SiswaImport, $fileName);

            $this->info('Berhasil! Data siswa dari kolom B telah diimport.');

        } catch (\Exception $e) {
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
        }

        return 0;
    }
}
