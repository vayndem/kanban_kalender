<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Di sebagian environment kolom ini sudah terlanjur ada tanpa lewat
        // migration. Tanpa pengaman ini, migrate berhenti dengan "duplicate
        // column" dan memblokir seluruh migration setelahnya.
        if (Schema::hasColumn('pakets', 'pertemuan')) {
            return;
        }

        Schema::table('pakets', function (Blueprint $table) {
            $table->integer('pertemuan')->default(3)->after('harga');
        });

        // Hanya untuk kolom yang baru dibuat. Dijalankan pada tabel yang sudah
        // berisi data, baris ini akan menimpa jumlah pertemuan setiap paket
        // menjadi 3 dan menghapus nilai asli (1, 2, 4, dst).
        DB::table('pakets')->update(['pertemuan' => 3]);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('pakets', 'pertemuan')) {
            return;
        }

        Schema::table('pakets', function (Blueprint $table) {
            $table->dropColumn('pertemuan');
        });
    }
};
