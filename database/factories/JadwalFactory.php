<?php

namespace Database\Factories;

// TAMBAHKAN INI untuk mengambil data master
use App\Models\Guru;
use App\Models\Hari;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;

use Illuminate\Database\Eloquent\Factories\Factory;

class JadwalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Kode ini berasumsi data master (Siswa, Guru, dll.) SUDAH ADA
        // saat seeder memanggil factory ini.
        return [
            'siswa_id' => Siswa::inRandomOrder()->first()->id,
            'mata_pelajaran_id' => MataPelajaran::inRandomOrder()->first()->id,
            'guru_id' => Guru::inRandomOrder()->first()->id,
            'hari_id' => Hari::inRandomOrder()->first()->id,
            'ruang_id' => Ruang::inRandomOrder()->first()->id,
            'sesi_id' => Sesi::inRandomOrder()->first()->id,
        ];
    }
}
