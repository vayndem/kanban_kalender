<?php

namespace Database\Seeders;

use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk menguji alur pembayaran di lokal, termasuk skenario yang
 * dulu bikin tagihan ganda.
 *
 * Jalankan: php artisan db:seed --class=PembayaranDemoSeeder
 *
 * Seeder ini hanya MENAMBAH baris; tidak menghapus atau mengubah data lama.
 */
class PembayaranDemoSeeder extends Seeder
{
    public function run(): void
    {
        $paket = Paket::firstOrCreate(
            ['nama_paket' => 'TKA SD/SMP 3X/Minggu'],
            ['harga' => 350000, 'pertemuan' => 3]
        );

        $paketKecil = Paket::firstOrCreate(
            ['nama_paket' => 'Reguler 2X/Minggu'],
            ['harga' => 250000, 'pertemuan' => 2]
        );

        $periode = Carbon::now()->format('Y-m');
        $label = Carbon::now()->translatedFormat('F Y');

        // 1. Keluarga dengan tagihan paket yang sudah dibayar sebagian.
        $siswaCicilan = Siswa::firstOrCreate(
            ['name' => 'DEMO Cicilan Sebagian'],
            ['no_hp' => '+6285600000001', 'kelas' => '7', 'paket_pembayaran' => $paket->id]
        );

        $tagihanCicilan = Pembayaran::firstOrCreate(
            [
                'id_siswa' => $siswaCicilan->id,
                'id_paket' => $paket->id,
                'periode' => $periode,
            ],
            [
                'no_hp' => $siswaCicilan->no_hp,
                'harga' => $paket->harga,
                'keterangan' => "Tagihan Paket {$paket->nama_paket} - {$label}",
                'status' => 1,
                'total_sudah_dibayar' => 150000,
            ]
        );

        PembayaranDetail::firstOrCreate(
            [
                'id_pembayaran' => $tagihanCicilan->id,
                'keterangan' => 'Cicilan pertama (demo)',
            ],
            ['pembayaran' => 150000]
        );

        // 2. Keluarga dengan dua anak, satu lunas satu belum -- untuk menguji
        //    penggabungan per no_hp dan progres bayar campuran.
        $noHpKeluarga = '+6285600000002';
        foreach ([['DEMO Kakak', 2, 250000], ['DEMO Adik', 0, 0]] as [$nama, $status, $dibayar]) {
            $anak = Siswa::firstOrCreate(
                ['name' => $nama],
                ['no_hp' => $noHpKeluarga, 'kelas' => '5', 'paket_pembayaran' => $paketKecil->id]
            );

            Pembayaran::firstOrCreate(
                [
                    'id_siswa' => $anak->id,
                    'id_paket' => $paketKecil->id,
                    'periode' => $periode,
                ],
                [
                    'no_hp' => $noHpKeluarga,
                    'harga' => $paketKecil->harga,
                    'keterangan' => "Tagihan Paket {$paketKecil->nama_paket} - {$label}",
                    'status' => $status,
                    'total_sudah_dibayar' => $dibayar,
                ]
            );
        }

        // 3. Tagihan bebas tanpa paket (buku) -- boleh berulang, tidak pernah
        //    ikut aturan anti-ganda maupun penagihan massal.
        $siswaBuku = Siswa::firstOrCreate(
            ['name' => 'DEMO Tagihan Buku'],
            ['no_hp' => '+6285600000003', 'kelas' => '9']
        );

        foreach (['Buku modul semester 1', 'Buku latihan soal'] as $index => $keterangan) {
            Pembayaran::firstOrCreate(
                [
                    'id_siswa' => $siswaBuku->id,
                    'keterangan' => $keterangan,
                ],
                [
                    'no_hp' => $siswaBuku->no_hp,
                    'id_paket' => null,
                    'periode' => null,
                    'harga' => 75000 + ($index * 10000),
                    'status' => 0,
                    'total_sudah_dibayar' => 0,
                ]
            );
        }

        // 4. Siswa berpaket yang BELUM ditagih -- sasaran uji penagihan massal.
        Siswa::firstOrCreate(
            ['name' => 'DEMO Belum Tertagih'],
            ['no_hp' => '+6285600000004', 'kelas' => '8', 'paket_pembayaran' => $paket->id]
        );

        $this->command?->info('Seeder demo pembayaran selesai.');
        $this->command?->line('  - DEMO Cicilan Sebagian : tagihan Rp 350.000, sudah dibayar Rp 150.000');
        $this->command?->line('  - DEMO Kakak / DEMO Adik: satu keluarga ('.$noHpKeluarga.'), status campuran');
        $this->command?->line('  - DEMO Tagihan Buku     : 2 tagihan bebas tanpa paket');
        $this->command?->line('  - DEMO Belum Tertagih   : sasaran uji Penagihan Massal');
    }
}
