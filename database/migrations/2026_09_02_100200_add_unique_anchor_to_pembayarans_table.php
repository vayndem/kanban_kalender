<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kunci terakhir anti-tagihan-ganda: UNIQUE(id_siswa, id_paket, periode).
 *
 * Dipisah dari migration penambahan kolom karena database produksi kemungkinan
 * SUDAH memuat duplikat lama. Kalau begitu, migration ini sengaja berhenti
 * dengan pesan yang jelas, bukan error SQL mentah -- bersihkan dulu datanya
 * dengan `php artisan pembayaran:audit-duplikat`, baru jalankan lagi.
 *
 * Catatan: baris dengan id_paket NULL (tagihan manual bebas, bukan paket)
 * tidak terkena batasan ini, karena MySQL/SQLite memperlakukan NULL sebagai
 * nilai yang selalu berbeda di unique index. Tagihan bebas tetap fleksibel.
 */
return new class extends Migration
{
    public function up(): void
    {
        $conflicts = $this->findConflicts();

        if ($conflicts->isNotEmpty()) {
            $lines = $conflicts->take(10)->map(
                fn ($row) => "  - id_siswa={$row->id_siswa}, id_paket={$row->id_paket}, periode={$row->periode} ({$row->jml} baris: id {$row->ids})"
            )->implode(PHP_EOL);

            throw new RuntimeException(
                'Migration dibatalkan: masih ada '.$conflicts->count().' kelompok tagihan ganda.'.PHP_EOL
                .$lines.PHP_EOL
                .'Jalankan "php artisan pembayaran:audit-duplikat" untuk melihat seluruh daftarnya, '
                .'rapikan dulu datanya, baru jalankan migrate lagi.'
            );
        }

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->unique(['id_siswa', 'id_paket', 'periode'], 'pembayarans_anchor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropUnique('pembayarans_anchor_unique');
        });
    }

    private function findConflicts()
    {
        return DB::table('pembayarans')
            ->select([
                'id_siswa',
                'id_paket',
                'periode',
                DB::raw('COUNT(*) as jml'),
                DB::raw(match (DB::connection()->getDriverName()) {
                    'sqlite' => 'GROUP_CONCAT(id) as ids',
                    default => 'GROUP_CONCAT(id ORDER BY id) as ids',
                }),
            ])
            ->whereNotNull('id_paket')
            ->whereNotNull('periode')
            ->groupBy('id_siswa', 'id_paket', 'periode')
            ->havingRaw('COUNT(*) > 1')
            ->get();
    }
};
