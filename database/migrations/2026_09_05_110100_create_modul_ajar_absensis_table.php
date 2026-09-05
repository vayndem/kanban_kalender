<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modul_ajar_absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modul_ajar_detail_id')->constrained('modul_ajar_details')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->boolean('hadir');
            $table->unsignedTinyInteger('nilai')->nullable();
            $table->timestamps();

            $table->unique(['modul_ajar_detail_id', 'siswa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modul_ajar_absensis');
    }
};
