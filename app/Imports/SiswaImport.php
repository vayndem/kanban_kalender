<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\Arsip;
use App\Models\Paket;
use App\Models\Jadwal;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeImport;

class SiswaImport implements ToModel, WithStartRow, WithEvents
{
    public function startRow(): int
    {
        return 5;
    }

    public function registerEvents(): array
    {
        return [
            BeforeImport::class => function (BeforeImport $event) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                Paket::truncate();
                Siswa::truncate();
                Jadwal::truncate();
                Pembayaran::truncate();
                DB::table('pembayaran_details')->truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            },
        ];
    }

    public function model(array $row)
    {
        $nama = isset($row[1]) ? trim($row[1]) : null;
        $namaPanggilan = isset($row[2]) ? trim($row[2]) : '-';
        $kelas = isset($row[3]) ? trim($row[3]) : '-';
        $statusArsip = isset($row[6]) ? trim($row[6]) : null;
        $rawPhone = isset($row[7]) ? trim($row[7]) : '-';

        $paket1 = isset($row[8]) ? trim($row[8]) : null;
        $paket2 = isset($row[9]) ? trim($row[9]) : null;

        if (str_contains((string) $paket1, ',')) {
            $exploded = explode(',', (string) $paket1);
            $paket1 = isset($exploded[0]) ? trim($exploded[0]) : null;
            $paket2 = isset($exploded[1]) ? trim($exploded[1]) : $paket2;
        }

        $idPaket_L = isset($row[11]) ? trim($row[11]) : null;
        $namaPaket_M = isset($row[12]) ? trim($row[12]) : null;
        $harga_N = isset($row[13]) ? trim($row[13]) : null;
        $pertemuan_O = isset($row[14]) ? trim($row[14]) : null;

        if (empty($nama) || $nama == "Nama Lengkap Siswa" || $nama == "NO.") {
            return null;
        }

        if (!empty($idPaket_L) && !empty($namaPaket_M)) {
            Paket::updateOrCreate(
                ['id' => (int) $idPaket_L],
                [
                    'nama_paket' => $namaPaket_M,
                    'harga'      => $harga_N,
                    'pertemuan'  => $pertemuan_O
                ]
            );
        }

        $data = [
            'name'               => $nama,
            'panggilan'          => $namaPanggilan,
            'kelas'              => $kelas,
            'no_hp'              => $rawPhone,
            'paket_pembayaran'   => $paket1 !== '' ? $paket1 : null,
            'paket_pembayaran_2' => $paket2 !== '' ? $paket2 : null,
        ];

        if ($statusArsip == 2) {
            dump("IMPORT KE ARSIP: " . $nama);
            return new Arsip($data);
        }

        dump("IMPORT KE SISWA: " . $nama);
        return new Siswa($data);
    }
}
