<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->unsignedBigInteger('gaji_bawaan')->default(0)->after('email');
            $table->unsignedBigInteger('gaji_per_kehadiran')->default(0)->after('gaji_bawaan');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn(['gaji_bawaan', 'gaji_per_kehadiran']);
        });
    }
};
