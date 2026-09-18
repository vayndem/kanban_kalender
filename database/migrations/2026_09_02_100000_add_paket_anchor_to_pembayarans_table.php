<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->unsignedBigInteger('id_paket')->nullable()->after('id_siswa');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->string('periode', 7)->nullable()->after('id_paket');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->index(['id_siswa', 'id_paket', 'periode'], 'pembayarans_anchor_index');
        });

        Schema::table('pembayarans', function (Blueprint $table) {
            $table->foreign('id_paket')->references('id')->on('pakets')->nullOnDelete();
        });

        $this->backfillAnchors();
    }

    public function down(): void
    {
        Schema::table('pembayarans', function (Blueprint $table) {
            $table->dropIndex('pembayarans_anchor_index');
            $table->dropForeign(['id_paket']);
            $table->dropColumn(['id_paket', 'periode']);
        });
    }

    private function backfillAnchors(): void
    {
        $packages = DB::table('pakets')->select('id', 'nama_paket')->get()
            ->sortByDesc(fn ($package) => mb_strlen((string) $package->nama_paket))
            ->values();

        if ($packages->isEmpty()) {
            return;
        }

        DB::table('pembayarans')
            ->select('id', 'keterangan', 'created_at')
            ->orderBy('id')
            ->chunkById(500, function ($payments) use ($packages) {
                foreach ($payments as $payment) {
                    $keterangan = (string) $payment->keterangan;
                    if ($keterangan === '') {
                        continue;
                    }

                    $matched = $packages->first(
                        fn ($package) => $package->nama_paket !== null
                            && $package->nama_paket !== ''
                            && str_contains($keterangan, (string) $package->nama_paket)
                    );

                    if (! $matched) {
                        continue;
                    }

                    DB::table('pembayarans')
                        ->where('id', $payment->id)
                        ->update([
                            'id_paket' => $matched->id,
                            'periode' => $payment->created_at
                                ? date('Y-m', strtotime((string) $payment->created_at))
                                : null,
                        ]);
                }
            });
    }
};
