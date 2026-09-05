<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modul_ajars', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kelas')->unique();
            $table->text('tujuan_pembelajaran');
            $table->text('kompetensi_awal');
            $table->string('model_pembelajaran');
            $table->text('sarana_media');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modul_ajars');
    }
};
