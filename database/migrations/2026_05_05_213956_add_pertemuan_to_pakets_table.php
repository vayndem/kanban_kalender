<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pakets', function (Blueprint $table) {
            $table->integer('pertemuan')->default(3)->after('harga');
        });

        DB::table('pakets')->update(['pertemuan' => 3]);
    }

    public function down(): void
    {
        Schema::table('pakets', function (Blueprint $table) {
            $table->dropColumn('pertemuan');
        });
    }
};
