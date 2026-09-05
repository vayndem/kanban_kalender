<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->string('kode_kelas')->nullable()->after('id')->index();
        });

        DB::table('jadwals')
            ->select(['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id'])
            ->distinct()
            ->get()
            ->each(function ($kelompok) {
                DB::table('jadwals')
                    ->where('hari_id', $kelompok->hari_id)
                    ->where('sesi_id', $kelompok->sesi_id)
                    ->where('mata_pelajaran_id', $kelompok->mata_pelajaran_id)
                    ->where('guru_id', $kelompok->guru_id)
                    ->where('ruang_id', $kelompok->ruang_id)
                    ->update(['kode_kelas' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropColumn('kode_kelas');
        });
    }
};
