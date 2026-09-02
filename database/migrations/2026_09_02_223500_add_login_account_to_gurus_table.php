<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('guru_id')->nullable()->unique()->after('id')
                ->constrained('gurus')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['guru_id']);
            $table->dropColumn('guru_id');
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
