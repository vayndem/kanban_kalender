<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->integer('paket_pembayaran_2')->nullable()->after('paket_pembayaran');
            $table->integer('paket_pembayaran_3')->nullable()->after('paket_pembayaran_2');
            $table->integer('paket_pembayaran_4')->nullable()->after('paket_pembayaran_3');
            $table->integer('paket_pembayaran_5')->nullable()->after('paket_pembayaran_4');
            $table->foreignId('tingkat_kemampuan_id')->nullable()->after('paket_pembayaran_5')
                ->constrained('tingkat_kemampuans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('arsips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tingkat_kemampuan_id');
            $table->dropColumn([
                'paket_pembayaran_2',
                'paket_pembayaran_3',
                'paket_pembayaran_4',
                'paket_pembayaran_5',
            ]);
        });
    }
};
