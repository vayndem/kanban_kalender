<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modul_ajar_details', function (Blueprint $table) {
            $table->boolean('tidak_bisa_hadir')->default(false)->after('sedang_dipersiapkan');
        });
    }

    public function down(): void
    {
        Schema::table('modul_ajar_details', function (Blueprint $table) {
            $table->dropColumn('tidak_bisa_hadir');
        });
    }
};
