<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertemuans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modul_ajar_detail_id')->constrained('modul_ajar_details')->cascadeOnDelete();
            $table->date('tanggal');
            $table->foreignId('guru_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->foreignId('guru_pengganti_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->timestamp('selesai_pada')->nullable();
            $table->timestamps();

            $table->index(['modul_ajar_detail_id', 'tanggal']);
            $table->index('tanggal');
        });

        $this->pindahkanPertemuanLama();

        Schema::table('modul_ajar_absensis', function (Blueprint $table) {
            $table->foreignId('pertemuan_id')->nullable()->after('id')->constrained('pertemuans')->cascadeOnDelete();
        });

        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->foreignId('pertemuan_id')->nullable()->after('id')->constrained('pertemuans')->cascadeOnDelete();
        });

        $this->sambungkanKeAbsensi();

        Schema::table('modul_ajar_absensis', function (Blueprint $table) {
            $table->dropForeign(['modul_ajar_detail_id']);
            $table->dropUnique('modul_ajar_absensis_modul_ajar_detail_id_siswa_id_unique');
            $table->dropColumn('modul_ajar_detail_id');
        });

        Schema::table('modul_ajar_absensis', function (Blueprint $table) {
            $table->unique(['pertemuan_id', 'siswa_id'], 'absensi_pertemuan_siswa_unik');
        });

        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->dropForeign(['modul_ajar_detail_id']);
            $table->dropIndex('absensi_gurus_detail_index');
            $table->dropColumn('modul_ajar_detail_id');
        });

        Schema::table('modul_ajar_details', function (Blueprint $table) {
            $table->dropForeign(['guru_pengganti_id']);
            $table->dropForeign(['diajarkan_oleh_guru_id']);
            $table->dropColumn([
                'sedang_dipersiapkan',
                'guru_pengganti_id',
                'diajarkan_oleh_guru_id',
                'tanggal_diajarkan',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('modul_ajar_details', function (Blueprint $table) {
            $table->boolean('sedang_dipersiapkan')->default(false);
            $table->foreignId('guru_pengganti_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->foreignId('diajarkan_oleh_guru_id')->nullable()->constrained('gurus')->nullOnDelete();
            $table->date('tanggal_diajarkan')->nullable();
        });

        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->foreignId('modul_ajar_detail_id')->nullable()->constrained('modul_ajar_details')->cascadeOnDelete();
            $table->index('modul_ajar_detail_id', 'absensi_gurus_detail_index');
        });

        Schema::table('modul_ajar_absensis', function (Blueprint $table) {
            $table->dropUnique('absensi_pertemuan_siswa_unik');
            $table->foreignId('modul_ajar_detail_id')->nullable()->constrained('modul_ajar_details')->cascadeOnDelete();
        });

        foreach (DB::table('pertemuans')->get() as $p) {
            DB::table('modul_ajar_absensis')->where('pertemuan_id', $p->id)
                ->update(['modul_ajar_detail_id' => $p->modul_ajar_detail_id]);
            DB::table('absensi_gurus')->where('pertemuan_id', $p->id)
                ->update(['modul_ajar_detail_id' => $p->modul_ajar_detail_id]);

            if ($p->selesai_pada) {
                DB::table('modul_ajar_details')->where('id', $p->modul_ajar_detail_id)->update([
                    'tanggal_diajarkan' => $p->tanggal,
                    'diajarkan_oleh_guru_id' => $p->guru_id,
                ]);
            }
        }

        Schema::table('modul_ajar_absensis', function (Blueprint $table) {
            $table->dropForeign(['pertemuan_id']);
            $table->dropColumn('pertemuan_id');
        });

        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->dropForeign(['pertemuan_id']);
            $table->dropColumn('pertemuan_id');
        });

        Schema::dropIfExists('pertemuans');
    }

    private function pindahkanPertemuanLama(): void
    {
        $sekarang = now();

        foreach (DB::table('modul_ajar_details')->get() as $detail) {
            $sedangJalan = (bool) ($detail->sedang_dipersiapkan ?? false);
            $sudahDiajar = $detail->tanggal_diajarkan !== null;

            if (! $sedangJalan && ! $sudahDiajar) {
                continue;
            }

            DB::table('pertemuans')->insert([
                'modul_ajar_detail_id' => $detail->id,
                'tanggal' => $detail->tanggal_diajarkan ?? $sekarang->toDateString(),
                'guru_id' => $detail->diajarkan_oleh_guru_id ?? $detail->guru_pengganti_id,
                'guru_pengganti_id' => $detail->guru_pengganti_id,
                'selesai_pada' => $sudahDiajar && ! $sedangJalan ? $sekarang : null,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);
        }
    }

    private function sambungkanKeAbsensi(): void
    {
        $peta = DB::table('pertemuans')->pluck('id', 'modul_ajar_detail_id');

        foreach ($peta as $detailId => $pertemuanId) {
            DB::table('modul_ajar_absensis')
                ->where('modul_ajar_detail_id', $detailId)
                ->update(['pertemuan_id' => $pertemuanId]);

            DB::table('absensi_gurus')
                ->where('modul_ajar_detail_id', $detailId)
                ->update(['pertemuan_id' => $pertemuanId]);
        }

        DB::table('modul_ajar_absensis')->whereNull('pertemuan_id')->delete();
        DB::table('absensi_gurus')->whereNull('pertemuan_id')->delete();
    }
};
