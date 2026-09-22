<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\TingkatKemampuan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KemampuanTanpaLevelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_tabelnya_tidak_lagi_punya_kolom_level(): void
    {
        $this->assertFalse(
            Schema::hasColumn('tingkat_kemampuans', 'level'),
            'Kemampuan bukan tingkatan bernomor lagi.'
        );
    }

    public function test_menambah_kemampuan_cukup_dengan_sebutannya(): void
    {
        $respon = $this->actingAs(User::factory()->create())
            ->postJson(route('admin.kemampuan.store'), ['keterangan' => 'Sudah lancar membaca']);

        $respon->assertOk();
        $this->assertStringContainsString('Sudah lancar membaca', $respon->json('message'));
        $this->assertDatabaseHas('tingkat_kemampuans', ['keterangan' => 'Sudah lancar membaca']);
    }

    public function test_sebutan_yang_sama_ditolak(): void
    {
        TingkatKemampuan::create(['keterangan' => 'Mahir']);

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.kemampuan.store'), ['keterangan' => 'Mahir'])
            ->assertStatus(422)
            ->assertJsonPath('errors.keterangan.0', 'Sebutan kemampuan itu sudah ada di daftar.');

        $this->assertSame(1, TingkatKemampuan::count());
    }

    public function test_kemampuan_mana_saja_boleh_dihapus_tanpa_urutan(): void
    {
        $dasar = TingkatKemampuan::create(['keterangan' => 'Dasar']);
        TingkatKemampuan::create(['keterangan' => 'Mahir']);

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.kemampuan.destroy', $dasar->id))
            ->assertOk();

        $this->assertSame(1, TingkatKemampuan::count(), 'Tidak ada lagi aturan hapus dari yang tertinggi.');
        $this->assertDatabaseHas('tingkat_kemampuans', ['keterangan' => 'Mahir']);
    }

    public function test_kemampuan_yang_masih_dipakai_siswa_tetap_tidak_bisa_dihapus(): void
    {
        $kemampuan = TingkatKemampuan::create(['keterangan' => 'Mahir']);
        Siswa::factory()->create(['tingkat_kemampuan_id' => $kemampuan->id]);

        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.kemampuan.destroy', $kemampuan->id));

        $respon->assertStatus(422);
        $this->assertStringContainsString('masih dipakai 1 siswa', $respon->json('message'));
        $this->assertSame(1, TingkatKemampuan::count());
    }

    public function test_daftar_kemampuan_di_workshop_terurut_abjad(): void
    {
        foreach (['Mahir', 'Dasar', 'Berkembang'] as $nama) {
            TingkatKemampuan::create(['keterangan' => $nama]);
        }

        $urutan = $this->actingAs(User::factory()->create())
            ->get(route('admin.workshop.index'))
            ->assertOk()
            ->viewData('kemampuans')
            ->pluck('keterangan')
            ->all();

        $this->assertSame(['Berkembang', 'Dasar', 'Mahir'], $urutan);
    }

    public function test_layar_tidak_lagi_menyebut_kata_level(): void
    {
        TingkatKemampuan::create(['keterangan' => 'Mahir']);

        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('admin.workshop.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Tambah Level', $halaman);
        $this->assertStringNotContainsString('Belum ada level kemampuan', $halaman);
    }
}
