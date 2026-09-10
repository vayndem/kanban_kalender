<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HariIniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_hari_ini_dicari_lewat_nama_bukan_nomor_id(): void
    {
        $namaHariIni = Hari::NAMA_ISO[(int) now()->isoFormat('E')];

        DB::table('haris')->insert(['id' => 90210, 'name' => $namaHariIni, 'created_at' => now(), 'updated_at' => now()]);

        $this->assertSame(90210, Hari::idHariIni(), 'Harus ketemu lewat nama, bukan menebak dari nomor ISO.');
    }

    public function test_jadwal_hari_ini_tetap_tampil_walau_id_hari_melompat(): void
    {
        $namaHariIni = Hari::NAMA_ISO[(int) now()->isoFormat('E')];
        DB::table('haris')->insert(['id' => 90210, 'name' => $namaHariIni, 'created_at' => now(), 'updated_at' => now()]);

        Jadwal::create([
            'hari_id' => 90210,
            'sesi_id' => Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00'])->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => 'Aljabar Khusus'])->id,
            'guru_id' => Guru::factory()->create(['name' => 'Pak Uji'])->id,
            'ruang_id' => Ruang::factory()->create(['name' => 'Ruang Uji'])->id,
            'siswa_id' => Siswa::factory()->create(['name' => 'Siswa Uji'])->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Aljabar Khusus')
            ->assertSee('Pak Uji');
    }

    public function test_jatuh_kembali_ke_nomor_iso_bila_nama_hari_tidak_ada(): void
    {
        $iso = (int) now()->isoFormat('E');
        $hari = new Hari(['name' => 'Hari Bebas']);
        $hari->id = $iso;
        $hari->save();

        $this->assertSame($iso, Hari::idHariIni());
    }
}
