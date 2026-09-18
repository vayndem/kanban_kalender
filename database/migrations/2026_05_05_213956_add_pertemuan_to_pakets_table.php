<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('pakets', 'pertemuan')) {
            return;
        }

        Schema::table('pakets', function (Blueprint $table) {
            $table->integer('pertemuan')->default(3)->after('harga');
        });

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
