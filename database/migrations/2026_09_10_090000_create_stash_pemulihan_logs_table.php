<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stash_pemulihan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dipulihkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('jumlah_sebelum')->default(0);
            $table->unsignedInteger('jumlah_sesudah')->default(0);
            $table->unsignedInteger('bentrok_masuk')->default(0);
            $table->longText('isi_sebelum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stash_pemulihan_logs');
    }
};
