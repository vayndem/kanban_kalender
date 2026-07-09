<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->integer('paket_pembayaran_2')->nullable()->after('paket_pembayaran');
            $table->integer('paket_pembayaran_3')->nullable()->after('paket_pembayaran_2');
            $table->integer('paket_pembayaran_4')->nullable()->after('paket_pembayaran_3');
            $table->integer('paket_pembayaran_5')->nullable()->after('paket_pembayaran_4');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('no_hp')->nullable()->after('id_siswa');
            $table->bigInteger('total_sudah_dibayar')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn([
                'paket_pembayaran_2',
                'paket_pembayaran_3',
                'paket_pembayaran_4',
                'paket_pembayaran_5'
            ]);
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropColumn([
                'no_hp',
                'total_sudah_dibayar'
            ]);
        });
    }
};
