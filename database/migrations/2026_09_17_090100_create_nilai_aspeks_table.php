<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nilai_aspeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modul_ajar_absensi_id')->constrained('modul_ajar_absensis')->cascadeOnDelete();
            $table->foreignId('aspek_penilaian_id')->constrained('aspek_penilaians')->restrictOnDelete();
            $table->unsignedTinyInteger('skor');
            $table->timestamps();

            $table->unique(['modul_ajar_absensi_id', 'aspek_penilaian_id'], 'nilai_aspek_unik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_aspeks');
    }
};
