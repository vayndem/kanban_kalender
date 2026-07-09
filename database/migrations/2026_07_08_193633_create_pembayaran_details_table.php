<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_pembayaran')->constrained('pembayarans')->onDelete('cascade');
            $table->integer('pembayaran');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_details');
    }
};
