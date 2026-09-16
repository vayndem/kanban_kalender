<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->dropForeign(['modul_ajar_detail_id']);
            $table->dropUnique('absensi_gurus_modul_ajar_detail_id_unique');
            $table->index('modul_ajar_detail_id', 'absensi_gurus_detail_index');
            $table->foreign('modul_ajar_detail_id')
                ->references('id')->on('modul_ajar_details')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->dropForeign(['modul_ajar_detail_id']);
            $table->dropIndex('absensi_gurus_detail_index');
            $table->unique('modul_ajar_detail_id', 'absensi_gurus_modul_ajar_detail_id_unique');
            $table->foreign('modul_ajar_detail_id')
                ->references('id')->on('modul_ajar_details')
                ->cascadeOnDelete();
        });
    }
};
