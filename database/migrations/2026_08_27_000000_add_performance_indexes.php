<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->index(['hari_id', 'sesi_id', 'guru_id'], 'jadwals_slot_guru_index');
            $table->index(['hari_id', 'sesi_id', 'ruang_id'], 'jadwals_slot_ruang_index');
            $table->index(['hari_id', 'sesi_id', 'siswa_id'], 'jadwals_slot_siswa_index');
            $table->index(
                ['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id'],
                'jadwals_class_index'
            );
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'pembayarans_status_created_index');
            $table->index(['no_hp', 'status', 'created_at'], 'pembayarans_phone_status_created_index');
            $table->index(['id_siswa', 'no_hp', 'keterangan'], 'pembayarans_invoice_lookup_index');
        });

        Schema::table('pembayaran_details', function (Blueprint $table) {
            $table->index(['id_pembayaran', 'created_at'], 'payment_details_payment_created_index');
        });

        Schema::table('diskons', function (Blueprint $table) {
            $table->index('no_hp', 'diskons_phone_index');
        });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropIndex('jadwals_slot_guru_index');
            $table->dropIndex('jadwals_slot_ruang_index');
            $table->dropIndex('jadwals_slot_siswa_index');
            $table->dropIndex('jadwals_class_index');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex('pembayarans_status_created_index');
            $table->dropIndex('pembayarans_phone_status_created_index');
            $table->dropIndex('pembayarans_invoice_lookup_index');
        });

        Schema::table('pembayaran_details', function (Blueprint $table) {
            $table->dropIndex('payment_details_payment_created_index');
        });

        Schema::table('diskons', function (Blueprint $table) {
            $table->dropIndex('diskons_phone_index');
        });
    }
};
