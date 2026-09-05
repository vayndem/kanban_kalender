<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modul_ajar_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modul_ajar_id')->constrained('modul_ajars')->cascadeOnDelete();
            $table->string('materi');
            $table->string('sub_materi')->nullable();
            $table->text('cara_mengajar')->nullable();
            $table->text('tugas')->nullable();
            $table->text('tujuan')->nullable();
            $table->text('hasil_akhir_pembelajaran')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modul_ajar_details');
    }
};
