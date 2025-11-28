<?php

namespace Database\Seeders;

use App\Models\Hari;
use App\Models\Sesi;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678'),
        ]);

        $haris = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        foreach ($haris as $hari) {
            Hari::create(['name' => $hari]);
        }

        Sesi::create([
            'name' => 'Sesi 1',
            'start_time' => '13:30',
            'end_time' => '15:00',
        ]);

        Sesi::create([
            'name' => 'Sesi 2',
            'start_time' => '15:30',
            'end_time' => '16:30',
        ]);

        Sesi::create([
            'name' => 'Sesi 3',
            'start_time' => '16:30',
            'end_time' => '17:30',
        ]);


        Sesi::create([
            'name' => 'Sesi 4',
            'start_time' => '18:30',
            'end_time' => '20:00',
        ]);

    }
}
