<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->unsignedBigInteger('tunjangan_fungsional')->default(0)->after('gaji_bawaan');
            $table->unsignedBigInteger('potongan')->default(0)->after('tunjangan_fungsional');
        });

        Schema::table('penggajians', function (Blueprint $table) {
            $table->unsignedBigInteger('tunjangan_fungsional')->default(0)->after('gaji_bawaan');
            $table->unsignedBigInteger('potongan')->default(0)->after('tunjangan_fungsional');
        });

        Schema::table('penggajians', function (Blueprint $table) {
            $table->bigInteger('total')->default(0)->change();
        });
    }

    public function down(): void
    {
        DB::table('penggajians')->where('total', '<', 0)->update(['total' => 0]);

        Schema::table('penggajians', function (Blueprint $table) {
            $table->unsignedBigInteger('total')->default(0)->change();
        });

        Schema::table('penggajians', function (Blueprint $table) {
            $table->dropColumn(['tunjangan_fungsional', 'potongan']);
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn(['tunjangan_fungsional', 'potongan']);
        });
    }
};
