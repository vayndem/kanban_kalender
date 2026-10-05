<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jejak_perubahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_pelaku')->nullable();
            $table->string('entitas', 32);
            $table->string('aksi', 32);
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->string('kode_kelas')->nullable();
            $table->string('ringkasan', 500);
            $table->json('detail')->nullable();
            $table->timestamps();

            $table->index(['entitas', 'created_at']);
            $table->index('kode_kelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jejak_perubahans');
    }
};
