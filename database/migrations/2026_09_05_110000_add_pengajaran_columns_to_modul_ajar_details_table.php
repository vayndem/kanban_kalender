<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modul_ajar_details', function (Blueprint $table) {
            $table->boolean('sedang_dipersiapkan')->default(false)->after('keterangan');
            $table->foreignId('guru_pengganti_id')->nullable()->after('sedang_dipersiapkan')->constrained('gurus')->nullOnDelete();
            $table->foreignId('diajarkan_oleh_guru_id')->nullable()->after('guru_pengganti_id')->constrained('gurus')->nullOnDelete();
            $table->date('tanggal_diajarkan')->nullable()->after('diajarkan_oleh_guru_id');
        });
    }

    public function down(): void
    {
        Schema::table('modul_ajar_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('guru_pengganti_id');
            $table->dropConstrainedForeignId('diajarkan_oleh_guru_id');
            $table->dropColumn(['sedang_dipersiapkan', 'tanggal_diajarkan']);
        });
    }
};
