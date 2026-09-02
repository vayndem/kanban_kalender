<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan "anchor" struktural pada tagihan: id_paket + periode.
 *
 * Sebelum ini, satu-satunya penanda bahwa sebuah tagihan adalah "tagihan paket X
 * untuk bulan Y" hanyalah teks bebas di kolom `keterangan`. Penagihan massal
 * mencocokkan teks itu persis, sehingga tagihan manual (yang formatnya berbeda)
 * tidak terdeteksi sebagai duplikat -> satu siswa bisa tertagih dua kali.
 *
 * Migration ini tidak menghapus atau mengubah data lama: hanya menambah kolom
 * dan mengisinya (backfill) berdasarkan keterangan + tanggal pembuatan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Operasi dipecah satu per satu: TiDB menolak sebagian kombinasi
        // perubahan skema bila digabung dalam satu statement ALTER.
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->unsignedBigInteger('id_paket')->nullable()->after('id_siswa');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('periode', 7)->nullable()->after('id_paket');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->index(['id_siswa', 'id_paket', 'periode'], 'pembayarans_anchor_index');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->foreign('id_paket')->references('id')->on('pakets')->nullOnDelete();
        });

        $this->backfillAnchors();
    }

    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex('pembayarans_anchor_index');
            $table->dropForeign(['id_paket']);
            $table->dropColumn(['id_paket', 'periode']);
        });
    }

    /**
     * Isi id_paket & periode untuk baris lama.
     *
     * Nama paket dicocokkan dari yang terpanjang lebih dulu supaya nama yang
     * saling mengandung (mis. "TKA SD" vs "TKA SD/SMP") tidak salah tebak.
     * Baris yang tidak cocok ke paket manapun sengaja dibiarkan NULL: itu
     * tagihan bebas/manual yang memang tidak ikut aturan tagihan bulanan.
     */
    private function backfillAnchors(): void
    {
        $packages = DB::table('pakets')->select('id', 'nama_paket')->get()
            ->sortByDesc(fn ($package) => mb_strlen((string) $package->nama_paket))
            ->values();

        if ($packages->isEmpty()) {
            return;
        }

        DB::table('pembayarans')
            ->select('id', 'keterangan', 'created_at')
            ->orderBy('id')
            ->chunkById(500, function ($payments) use ($packages) {
                foreach ($payments as $payment) {
                    $keterangan = (string) $payment->keterangan;
                    if ($keterangan === '') {
                        continue;
                    }

                    $matched = $packages->first(
                        fn ($package) => $package->nama_paket !== null
                            && $package->nama_paket !== ''
                            && str_contains($keterangan, (string) $package->nama_paket)
                    );

                    if (! $matched) {
                        continue;
                    }

                    DB::table('pembayarans')
                        ->where('id', $payment->id)
                        ->update([
                            'id_paket' => $matched->id,
                            'periode' => $payment->created_at
                                ? date('Y-m', strtotime((string) $payment->created_at))
                                : null,
                        ]);
                }
            });
    }
};
