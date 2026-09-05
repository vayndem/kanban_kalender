<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus');
            $table->foreignId('modul_ajar_detail_id')->unique()->constrained('modul_ajar_details')->cascadeOnDelete();
            $table->date('tanggal');
            // Dipakai kelak oleh fitur "tutup kas" penggajian. Selalu null untuk sekarang --
            // penghitungan absen tetap dilakukan dengan menjumlahkan baris per rentang tanggal.
            $table->timestamp('ditutup_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi_gurus');
    }
};
