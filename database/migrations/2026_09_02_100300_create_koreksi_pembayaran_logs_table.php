<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Arsip koreksi tagihan ganda.
 *
 * Baris tagihan yang dibuang tidak hilang begitu saja: seluruh isinya
 * (beserta detail pembayarannya) disalin utuh ke sini dalam bentuk JSON
 * sebelum dihapus. Ini yang menjaga kewajiban retensi pembukuan -- jejaknya
 * tetap ada dan bisa ditelusuri, tanpa mengotori laporan berjalan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('koreksi_pembayaran_logs', function (Blueprint $table) {
            $table->id();
            $table->string('kelompok', 64);
            $table->unsignedBigInteger('id_pembayaran_induk');
            $table->unsignedBigInteger('id_pembayaran_dibuang');
            $table->integer('nilai_tagihan_dibuang')->default(0);
            $table->integer('nilai_detail_dibuang')->default(0);
            $table->string('alasan', 255);
            $table->longText('data_asli');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('kelompok');
            $table->index('id_pembayaran_induk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('koreksi_pembayaran_logs');
    }
};
