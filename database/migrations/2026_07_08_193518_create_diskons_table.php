<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diskons', function (Blueprint $table) {
            $table->id();
            $table->string('no_hp', 255)->nullable();
            $table->integer('diskon');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diskons');
    }
};
