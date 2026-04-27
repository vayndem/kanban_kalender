<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\Arsip;
use App\Models\Paket;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SiswaImport implements ToModel, WithStartRow
{
    public function startRow(): int
    {
        return 2;
    }

    public function model(array $row)
    {
        // --- DATA SISWA & ARSIP (Sesuai Logika Lama Kamu, TIDAK DISENTUH) ---
        $nama          = isset($row[2]) ? trim($row[2]) : null;
        $namaPanggilan = isset($row[3]) ? trim($row[3]) : '-';
        $kelas         = isset($row[5]) ? trim($row[5]) : '-';
        $statusArsip   = isset($row[6]) ? trim($row[6]) : null;
        $paketID_H     = isset($row[7]) ? trim($row[7]) : null; // Ambil dari H
        $rawPhone      = isset($row[8]) ? trim($row[8]) : '-';

        // --- DATA KHUSUS UNTUK TABEL PAKETS (Ambil dari M & N) ---
        $namaPaket_M   = isset($row[12]) ? trim($row[12]) : null;
        $idPaket_N     = isset($row[13]) ? trim($row[13]) : null;

        if (empty($nama) || $nama == "Nama Lengkap" || $nama == "NO") {
            return null;
        }

        // --- PENGEMBANGAN: INPUT KE TABEL PAKETS ---
        // Paksa input ke tabel pakets pakai ID dari N (13) dan Nama dari M (12)
        if (!empty($idPaket_N) && !empty($namaPaket_M)) {
            Paket::updateOrCreate(
                ['id' => (int)$idPaket_N],
                [
                    'nama_paket' => $namaPaket_M,
                    'harga'      => null
                ]
            );
        }

        // Format No HP (Logika Lama)
        $formattedPhone = $rawPhone;
        if (!empty($rawPhone) && $rawPhone !== '-') {
            $cleaned = preg_replace('/[^0-9]/', '', (string)$rawPhone);
            if (str_starts_with($cleaned, '0')) {
                $formattedPhone = '+62' . substr($cleaned, 1);
            } elseif (str_starts_with($cleaned, '8')) {
                $formattedPhone = '+62' . $cleaned;
            }
        }

        // --- DATA UNTUK SISWA / ARSIP ---
        // Tetap pakai $paketID_H sesuai permintaan (Jangan disentuh)
        $data = [
            'name'             => $nama,
            'panggilan'        => $namaPanggilan,
            'kelas'            => $kelas,
            'no_hp'            => $formattedPhone,
            'paket_pembayaran' => $paketID_H,
        ];

        if ($statusArsip == 2) {
            dump("IMPORT KE ARSIP: " . $nama);
            return new Arsip($data);
        }

        dump("IMPORT KE SISWA: " . $nama);
        return new Siswa($data);
    }
}
