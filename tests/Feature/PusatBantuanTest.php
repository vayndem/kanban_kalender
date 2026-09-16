<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\User;
use App\Support\PusatBantuan;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PusatBantuanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_setiap_panduan_punya_judul_ringkasan_dan_isi(): void
    {
        foreach (PusatBantuan::semua() as $kunci => $panduan) {
            $this->assertNotSame('', trim($panduan['title']), "Judul kosong pada $kunci");
            $this->assertNotSame('', trim($panduan['summary']), "Ringkasan kosong pada $kunci");
            $this->assertNotEmpty($panduan['sections'], "Tidak ada bagian pada $kunci");

            foreach ($panduan['sections'] as $bagian) {
                $this->assertNotSame('', trim($bagian['title']), "Judul bagian kosong pada $kunci");
                $this->assertNotEmpty($bagian['items'], "Bagian '{$bagian['title']}' kosong pada $kunci");

                foreach ($bagian['items'] as $poin) {
                    $this->assertGreaterThan(20, mb_strlen($poin), "Poin terlalu pendek pada $kunci: $poin");
                }
            }
        }
    }

    public function test_setiap_tab_dashboard_punya_panduan_sendiri(): void
    {
        $bawaan = PusatBantuan::untuk('rute.tidak.dikenal');

        foreach (['jadwal', 'data_siswa', 'pembayaran', 'ringkasan', 'payroll'] as $tab) {
            $panduan = PusatBantuan::untuk('dashboard', $tab);
            $this->assertNotSame($bawaan['title'], $panduan['title'], "Tab $tab masih memakai panduan bawaan.");
        }
    }

    public function test_rute_tanpa_panduan_tetap_mendapat_panduan_bawaan(): void
    {
        $panduan = PusatBantuan::untuk(null);

        $this->assertSame('Pusat Bantuan', $panduan['title']);
        $this->assertNotEmpty($panduan['sections']);
    }

    public function test_halaman_admin_menampilkan_panduan_yang_sesuai(): void
    {
        $admin = User::factory()->create();

        $kasus = [
            [route('dashboard', ['tab' => 'jadwal']), 'Panduan Jadwal Pelajaran'],
            [route('dashboard', ['tab' => 'data_siswa']), 'Panduan Data Siswa'],
            [route('dashboard', ['tab' => 'pembayaran']), 'Panduan Pembayaran'],
            [route('dashboard', ['tab' => 'ringkasan']), 'Panduan Ringkasan'],
            [route('dashboard', ['tab' => 'payroll']), 'Panduan Payroll Guru'],
            [route('admin.workshop.index'), 'Panduan Workshop'],
            [route('admin.akunGuru.index'), 'Panduan Akun Guru'],
            [route('profile.edit'), 'Panduan Profil'],
        ];

        foreach ($kasus as [$url, $judul]) {
            $this->actingAs($admin)->get($url)->assertOk()->assertSee($judul, false);
        }
    }

    public function test_halaman_guru_juga_punya_tombol_bantuan(): void
    {
        $guru = Guru::factory()->create();
        $user = User::factory()->guru($guru)->create();

        $this->actingAs($user)->get(route('guru.jadwal'))->assertOk()->assertSee('Panduan Portal Guru', false);
        $this->actingAs($user)->get(route('guru.gaji'))->assertOk()->assertSee('Panduan Gaji Guru', false);
        $this->actingAs($user)->get(route('modulAjar.index'))->assertOk()->assertSee('Panduan Modul Ajar', false);
        $this->actingAs($user)->get(route('absen.index'))->assertOk()->assertSee('Panduan Absen', false);
    }
}
