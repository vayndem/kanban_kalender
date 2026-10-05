<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ketersediaan_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('hari_id')->constrained('haris')->cascadeOnDelete();
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('alasan')->nullable();
            $table->timestamps();

            $table->index(['guru_id', 'hari_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ketersediaan_gurus');
    }
};
