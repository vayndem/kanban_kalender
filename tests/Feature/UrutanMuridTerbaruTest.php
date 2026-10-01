<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class UrutanMuridTerbaruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Nama sengaja dibuat berlawanan dengan urutan pendaftaran, supaya
     * urutan abjad dan urutan terbaru tidak mungkin tertukar diam-diam.
     */
    private function tigaMurid(): void
    {
        Carbon::setTestNow('2026-01-10 08:00:00');
        Siswa::factory()->create(['name' => 'Ani Paling Lama']);

        Carbon::setTestNow('2026-05-10 08:00:00');
        Siswa::factory()->create(['name' => 'Budi Tengah']);

        Carbon::setTestNow('2026-09-10 08:00:00');
        Siswa::factory()->create(['name' => 'Cici Paling Baru']);

        Carbon::setTestNow();
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $daftar
     * @return list<string>
     */
    private function nama($daftar): array
    {
        return collect($daftar)->map(fn ($s) => is_array($s) ? ($s['nama'] ?? $s['name']) : $s->name)->all();
    }

    public function test_tab_data_siswa_menaruh_murid_terbaru_di_atas(): void
    {
        $this->tigaMurid();

        $daftar = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'data_siswa']))
            ->assertOk()
            ->viewData('allSiswas');

        $this->assertSame(['Cici Paling Baru', 'Budi Tengah', 'Ani Paling Lama'], $this->nama($daftar));
    }

    public function test_tab_jadwal_dan_pembayaran_ikut_terbaru_di_atas(): void
    {
        $this->tigaMurid();
        $admin = User::factory()->create();

        foreach (['jadwal', 'pembayaran'] as $tab) {
            $daftar = $this->actingAs($admin)
                ->get(route('dashboard', ['tab' => $tab]))
                ->assertOk()
                ->viewData('allSiswas');

            $this->assertSame(
                ['Cici Paling Baru', 'Budi Tengah', 'Ani Paling Lama'],
                $this->nama($daftar),
                "Tab {$tab} harus ikut aturan yang sama."
            );
        }
    }

    public function test_workshop_menaruh_murid_terbaru_di_atas(): void
    {
        $this->tigaMurid();

        $daftar = $this->actingAs(User::factory()->create())
            ->get(route('admin.workshop.index'))
            ->assertOk()
            ->viewData('siswas');

        $this->assertSame(['Cici Paling Baru', 'Budi Tengah', 'Ani Paling Lama'], $this->nama($daftar));
    }

    public function test_kartu_result_menaruh_murid_terbaru_di_atas(): void
    {
        $this->tigaMurid();

        $daftar = $this->actingAs(User::factory()->create())
            ->get(route('admin.result.index'))
            ->assertOk()
            ->viewData('siswaList');

        $this->assertSame(['Cici Paling Baru', 'Budi Tengah', 'Ani Paling Lama'], $this->nama($daftar));
        $this->assertNotNull($daftar[0]['created_at'], 'Kartu harus membawa created_at supaya bisa diurutkan di layar.');
    }

    public function test_arsip_menaruh_yang_terbaru_diarsipkan_di_atas(): void
    {
        Carbon::setTestNow('2026-02-01 08:00:00');
        Arsip::create(['name' => 'Arsip Lama', 'kelas' => '4']);

        Carbon::setTestNow('2026-08-01 08:00:00');
        Arsip::create(['name' => 'Arsip Baru', 'kelas' => '5']);

        Carbon::setTestNow();

        $daftar = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.arsip.index'))
            ->assertOk()
            ->json();

        $this->assertSame(['Arsip Baru', 'Arsip Lama'], array_column($daftar, 'name'));
    }

    public function test_urutan_tetap_benar_walau_dua_murid_mendaftar_di_detik_yang_sama(): void
    {
        Carbon::setTestNow('2026-07-01 08:00:00');
        $duluan = Siswa::factory()->create(['name' => 'Zulu Duluan']);
        $belakangan = Siswa::factory()->create(['name' => 'Alfa Belakangan']);
        Carbon::setTestNow();

        $daftar = $this->actingAs(User::factory()->create())
            ->get(route('admin.workshop.index'))
            ->assertOk()
            ->viewData('siswas');

        $this->assertSame(
            [$belakangan->name, $duluan->name],
            $this->nama($daftar),
            'Kalau created_at sama persis, id yang lebih besar dianggap lebih baru.'
        );
    }
}
