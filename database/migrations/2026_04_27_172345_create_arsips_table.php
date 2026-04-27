<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsips', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('panggilan')->nullable();
            $table->string('kelas')->nullable();
            $table->string('no_hp')->nullable();
            $table->integer('paket_pembayaran')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::connection('arsips')->dropIfExists('arsips');
    }
};
