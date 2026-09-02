<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log eksekusi aksi massal pembayaran (penagihan massal & pelunasan massal).
 *
 * Dua fungsi sekaligus:
 * 1. Kunci "sekali per bulan" -- UNIQUE(jenis, periode) dijaga database,
 *    bukan sekadar pengecekan di aplikasi, sehingga dua klik bersamaan
 *    tidak bisa lolos berbarengan.
 * 2. Jejak audit -- siapa yang menjalankan dan kapan, yang sebelumnya
 *    tidak tercatat sama sekali untuk aksi yang menyentuh uang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_pembayaran_logs', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 32);
            $table->string('periode', 7);
            $table->unsignedInteger('jumlah_diproses')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jenis', 'periode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_pembayaran_logs');
    }
};
