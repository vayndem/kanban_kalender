<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penggajians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();

            // Tarif dibekukan saat struk terbit supaya perubahan gaji di kemudian hari
            // tidak mengubah struk yang sudah dibayarkan.
            $table->unsignedInteger('jumlah_kehadiran')->default(0);
            $table->unsignedBigInteger('gaji_bawaan')->default(0);
            $table->unsignedBigInteger('gaji_per_kehadiran')->default(0);
            $table->unsignedBigInteger('total')->default(0);

            $table->foreignId('dijalankan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dijalankan_pada');

            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dibatalkan_pada')->nullable();
            $table->string('alasan_batal')->nullable();

            $table->timestamps();

            $table->index(['guru_id', 'dijalankan_pada']);
        });

        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->foreignId('penggajian_id')->nullable()->after('modul_ajar_detail_id')
                ->constrained('penggajians')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('absensi_gurus', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penggajian_id');
        });

        Schema::dropIfExists('penggajians');
    }
};
