<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tandas', function (Blueprint $table) {
            $table->id();

            // Kolom siswa_id yang terhubung ke tabel 'siswas'
            // constrained('siswas') memastikan ini nge-link ke tabel yang ada di screenshot Anda
            // onDelete('cascade') berarti jika siswa dihapus, tandanya ikut terhapus (opsional)
            $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');

            // Kolom keterangan
            $table->text('keterangan'); // Saya pakai text agar muat panjang, bisa diganti string() jika pendek

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tandas');
    }
};
