<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\Tanda;
use App\Models\TingkatKemampuan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArsipSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * @return array{siswa: Siswa, kemampuan: TingkatKemampuan, paket: array<int, Paket>}
     */
    private function siswaLengkap(): array
    {
        $paket = collect(range(1, 5))
            ->map(fn (int $i) => Paket::create([
                'nama_paket' => "Paket {$i}",
                'harga' => 100_000 * $i,
                'pertemuan' => $i,
            ]))
            ->all();

        $kemampuan = TingkatKemampuan::create(['keterangan' => 'Menengah']);

        $siswa = Siswa::factory()->create([
            'name' => 'Rani Puspita',
            'panggilan' => 'Rani',
            'kelas' => '9',
            'no_hp' => '081234567890',
            'paket_pembayaran' => $paket[0]->id,
            'paket_pembayaran_2' => $paket[1]->id,
            'paket_pembayaran_3' => $paket[2]->id,
            'paket_pembayaran_4' => $paket[3]->id,
            'paket_pembayaran_5' => $paket[4]->id,
            'tingkat_kemampuan_id' => $kemampuan->id,
        ]);

        return compact('siswa', 'kemampuan', 'paket');
    }

    private function arsipkan(Siswa $siswa): void
    {
        $this->actingAs($this->admin())
            ->deleteJson(route('admin.siswa.destroy', $siswa->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');
    }

    public function test_guest_cannot_restore_or_purge_an_archive_row(): void
    {
        $arsip = Arsip::create(['name' => 'Rani']);

        $this->put(route('admin.arsip.restore', $arsip->id))->assertRedirect(route('login'));
        $this->delete(route('admin.arsip.destroy', $arsip->id))->assertRedirect(route('login'));

        $this->assertDatabaseCount('arsips', 1);
        $this->assertDatabaseCount('siswas', 0);
    }

    public function test_teacher_account_is_forbidden_from_the_archive(): void
    {
        $guru = User::factory()->guru()->create();
        $arsip = Arsip::create(['name' => 'Rani']);

        $this->actingAs($guru)->putJson(route('admin.arsip.restore', $arsip->id))->assertForbidden();
        $this->actingAs($guru)->deleteJson(route('admin.arsip.destroy', $arsip->id))->assertForbidden();

        $this->assertDatabaseCount('arsips', 1);
    }

    public function test_halaman_arsip_mengarahkan_ke_tab_data_siswa_bukan_error(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.arsip.index'))
            ->assertRedirect(route('dashboard', ['tab' => 'data_siswa']));
    }

    public function test_daftar_arsip_masih_bisa_diambil_sebagai_json(): void
    {
        Arsip::create(['name' => 'Rani']);
        Arsip::create(['name' => 'Bima']);

        $this->actingAs($this->admin())
            ->getJson(route('admin.arsip.index'))
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_restore_is_reachable_only_through_put_never_post(): void
    {
        $arsip = Arsip::create(['name' => 'Rani']);

        $this->actingAs($this->admin())
            ->postJson('/admin/arsip/'.$arsip->id)
            ->assertStatus(405);

        $this->assertDatabaseHas('arsips', ['id' => $arsip->id]);
        $this->assertDatabaseCount('siswas', 0);
    }

    public function test_archiving_keeps_every_package_slot_and_the_ability_level(): void
    {
        ['siswa' => $siswa, 'kemampuan' => $kemampuan, 'paket' => $paket] = $this->siswaLengkap();

        $this->arsipkan($siswa);

        $this->assertDatabaseMissing('siswas', ['id' => $siswa->id]);
        $this->assertDatabaseHas('arsips', [
            'name' => 'Rani Puspita',
            'panggilan' => 'Rani',
            'kelas' => '9',
            'no_hp' => '+6281234567890',
            'paket_pembayaran' => $paket[0]->id,
            'paket_pembayaran_2' => $paket[1]->id,
            'paket_pembayaran_3' => $paket[2]->id,
            'paket_pembayaran_4' => $paket[3]->id,
            'paket_pembayaran_5' => $paket[4]->id,
            'tingkat_kemampuan_id' => $kemampuan->id,
        ]);
    }

    public function test_archiving_clears_the_schedules_and_notes_that_belonged_to_the_student(): void
    {
        ['siswa' => $siswa] = $this->siswaLengkap();

        Jadwal::create([
            'siswa_id' => $siswa->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => 'Matematika'])->id,
            'guru_id' => Guru::factory()->create(['name' => 'Bu Rina'])->id,
            'hari_id' => Hari::factory()->create(['name' => 'Senin'])->id,
            'ruang_id' => Ruang::factory()->create(['name' => 'Ruang A'])->id,
            'sesi_id' => Sesi::factory()->create(['name' => 'Sesi 1', 'start_time' => '13:00', 'end_time' => '14:00'])->id,
        ]);
        Tanda::create(['siswa_id' => $siswa->id, 'keterangan' => 'Sering telat']);

        $this->arsipkan($siswa);

        $this->assertDatabaseMissing('jadwals', ['siswa_id' => $siswa->id]);
        $this->assertDatabaseMissing('tandas', ['siswa_id' => $siswa->id]);
    }

    public function test_archiving_several_students_in_one_call(): void
    {
        $satu = Siswa::factory()->create(['name' => 'Rani']);
        $dua = Siswa::factory()->create(['name' => 'Bima']);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.siswa.destroy', $satu->id.','.$dua->id))
            ->assertOk();

        $this->assertDatabaseCount('siswas', 0);
        $this->assertDatabaseCount('arsips', 2);
    }

    public function test_archiving_an_unknown_student_reports_not_found(): void
    {
        $this->actingAs($this->admin())
            ->deleteJson(route('admin.siswa.destroy', 9999))
            ->assertStatus(404)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('arsips', 0);
    }

    public function test_restoring_brings_back_every_package_slot_and_the_ability_level(): void
    {
        ['siswa' => $siswa, 'kemampuan' => $kemampuan, 'paket' => $paket] = $this->siswaLengkap();

        $this->arsipkan($siswa);
        $arsip = Arsip::firstOrFail();

        $this->actingAs($this->admin())
            ->putJson(route('admin.arsip.restore', $arsip->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('siswas', [
            'name' => 'Rani Puspita',
            'panggilan' => 'Rani',
            'kelas' => '9',
            'no_hp' => '+6281234567890',
            'paket_pembayaran' => $paket[0]->id,
            'paket_pembayaran_2' => $paket[1]->id,
            'paket_pembayaran_3' => $paket[2]->id,
            'paket_pembayaran_4' => $paket[3]->id,
            'paket_pembayaran_5' => $paket[4]->id,
            'tingkat_kemampuan_id' => $kemampuan->id,
        ]);
    }

    public function test_a_full_archive_then_restore_round_trip_loses_nothing(): void
    {
        ['siswa' => $siswa] = $this->siswaLengkap();
        $sebelum = $siswa->only([
            'name', 'panggilan', 'kelas', 'no_hp',
            'paket_pembayaran', 'paket_pembayaran_2', 'paket_pembayaran_3',
            'paket_pembayaran_4', 'paket_pembayaran_5', 'tingkat_kemampuan_id',
        ]);

        $this->arsipkan($siswa);
        $this->actingAs($this->admin())
            ->putJson(route('admin.arsip.restore', Arsip::firstOrFail()->id))
            ->assertOk();

        $sesudah = Siswa::firstOrFail()->only(array_keys($sebelum));

        $this->assertSame($sebelum, $sesudah);
    }

    public function test_restoring_removes_the_row_from_the_archive(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Rani']);
        $this->arsipkan($siswa);
        $arsip = Arsip::firstOrFail();

        $this->actingAs($this->admin())
            ->putJson(route('admin.arsip.restore', $arsip->id))
            ->assertOk();

        $this->assertDatabaseCount('arsips', 0);
        $this->assertDatabaseCount('siswas', 1);
    }

    public function test_restoring_twice_reports_not_found_the_second_time(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Rani']);
        $this->arsipkan($siswa);
        $arsip = Arsip::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->putJson(route('admin.arsip.restore', $arsip->id))->assertOk();
        $this->actingAs($admin)->putJson(route('admin.arsip.restore', $arsip->id))
            ->assertStatus(404)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('siswas', 1);
    }

    public function test_purging_an_archive_row_removes_it_without_recreating_the_student(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Rani']);
        $this->arsipkan($siswa);
        $arsip = Arsip::firstOrFail();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.arsip.destroy', $arsip->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseCount('arsips', 0);
        $this->assertDatabaseCount('siswas', 0);
    }

    public function test_purging_something_already_gone_reports_not_found(): void
    {
        $this->actingAs($this->admin())
            ->deleteJson(route('admin.arsip.destroy', 9999))
            ->assertStatus(404)
            ->assertJsonPath('status', 'error');
    }

    public function test_purging_one_row_leaves_the_other_archive_rows_alone(): void
    {
        $satu = Arsip::create(['name' => 'Rani']);
        $dua = Arsip::create(['name' => 'Bima']);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.arsip.destroy', $satu->id))
            ->assertOk();

        $this->assertDatabaseMissing('arsips', ['id' => $satu->id]);
        $this->assertDatabaseHas('arsips', ['id' => $dua->id, 'name' => 'Bima']);
    }
}
