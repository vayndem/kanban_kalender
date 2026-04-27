<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\SiswaImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportSiswa extends Command
{
    protected $signature = 'siswa:import';

    protected $description = 'Import data siswa dari storage/app/1.xlsx';

    public function handle()
    {
        $filePath = storage_path('app/1.xlsx');

        if (!file_exists($filePath)) {
            $this->error("FILE TIDAK ADA DI: " . $filePath);
            return 1;
        }

        $this->info("Sedang memproses import data...");

        try {
            Excel::import(new SiswaImport, $filePath);
            $this->info("Berhasil mengimport data siswa, arsip, dan paket.");
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
