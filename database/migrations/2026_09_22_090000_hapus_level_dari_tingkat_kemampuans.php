<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->gabungkanKeteranganGanda();

        Schema::table('tingkat_kemampuans', function (Blueprint $table) {
            $table->dropUnique('tingkat_kemampuans_level_unique');
        });

        Schema::table('tingkat_kemampuans', function (Blueprint $table) {
            $table->dropColumn('level');
        });

        Schema::table('tingkat_kemampuans', function (Blueprint $table) {
            $table->unique('keterangan', 'tingkat_kemampuans_keterangan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tingkat_kemampuans', function (Blueprint $table) {
            $table->dropUnique('tingkat_kemampuans_keterangan_unique');
        });

        Schema::table('tingkat_kemampuans', function (Blueprint $table) {
            $table->unsignedTinyInteger('level')->default(0)->after('id');
        });

        $urutan = 1;
        foreach (DB::table('tingkat_kemampuans')->orderBy('id')->pluck('id') as $id) {
            DB::table('tingkat_kemampuans')->where('id', $id)->update(['level' => $urutan++]);
        }

        Schema::table('tingkat_kemampuans', function (Blueprint $table) {
            $table->unique('level');
        });
    }

    private function gabungkanKeteranganGanda(): void
    {
        $perKeterangan = DB::table('tingkat_kemampuans')
            ->orderBy('id')
            ->get(['id', 'keterangan'])
            ->groupBy(fn ($baris) => mb_strtolower(trim((string) $baris->keterangan)));

        foreach ($perKeterangan as $baris) {
            if ($baris->count() < 2) {
                continue;
            }

            $induk = $baris->first()->id;
            $dibuang = $baris->slice(1)->pluck('id');

            DB::table('siswas')->whereIn('tingkat_kemampuan_id', $dibuang)->update(['tingkat_kemampuan_id' => $induk]);
            DB::table('arsips')->whereIn('tingkat_kemampuan_id', $dibuang)->update(['tingkat_kemampuan_id' => $induk]);
            DB::table('tingkat_kemampuans')->whereIn('id', $dibuang)->delete();
        }
    }
};
