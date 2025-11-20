<?php

namespace Database\Seeders;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat User Bawaan
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678'),
        ]);

        // 2. Buat Data Master yang PASTI (Manual)
        Hari::create(['name' => 'Senin']);
        Hari::create(['name' => 'Selasa']);
        Hari::create(['name' => 'Rabu']);
        Hari::create(['name' => 'Kamis']);
        Hari::create(['name' => 'Jumat']);

        Sesi::create(['name' => 'Jam 1-2', 'start_time' => '07:00', 'end_time' => '08:30']);
        Sesi::create(['name' => 'Jam 3-4', 'start_time' => '08:30', 'end_time' => '10:00']);
        Sesi::create(['name' => 'Jam 5-6', 'start_time' => '10:30', 'end_time' => '12:00']);

        collect(['Matematika', 'Bahasa Indonesia', 'Biologi', 'Fisika', 'Kimia', 'Sejarah', 'Geografi'])->each(fn($name) => MataPelajaran::create(['name' => $name]));

        // 3. Buat Data Master pakai Factory
        Siswa::factory(20)->create(); // Buat 20 siswa
        Guru::factory(10)->create(); // Buat 10 guru
        Ruang::factory(5)->create(); // Buat 5 ruang

        Jadwal::factory(10)->create();
    }
}
