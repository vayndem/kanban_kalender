<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapor_cetaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->foreignId('dicetak_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('periode_label')->nullable();
            $table->json('pertemuan_ids');
            $table->text('kekuatan')->nullable();
            $table->text('perbaikan')->nullable();
            $table->text('komentar')->nullable();
            $table->text('rencana')->nullable();
            $table->timestamps();

            $table->index(['siswa_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapor_cetaks');
    }
};
