<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopTest extends TestCase
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

    private function buatKelas(string $kelas): array
    {
        $guru = Guru::factory()->create(['name' => 'Bu Rina']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $mapel = MataPelajaran::factory()->create(['name' => 'Matematika']);
        $sesi = Sesi::factory()->create(['name' => 'Sesi 1', 'start_time' => '13:00', 'end_time' => '14:00']);
        $hari = Hari::factory()->create(['name' => 'Senin']);
        $siswa = Siswa::factory()->create(['kelas' => $kelas]);

        Jadwal::create([
            'siswa_id' => $siswa->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'hari_id' => $hari->id,
            'ruang_id' => $ruang->id,
            'sesi_id' => $sesi->id,
        ]);

        return compact('guru', 'ruang', 'mapel', 'sesi', 'hari', 'siswa');
    }

    public function test_page_shows_availability_map_and_reference_data(): void
    {
        $this->buatKelas('12');

        $response = $this->actingAs($this->admin())->get(route('admin.workshop.index'));

        $response->assertOk()
            ->assertSee('Slot Kosong per Hari')
            ->assertSee('Bu Rina')
            ->assertSee('Ruang A')
            ->assertSee('Matematika')
            ->assertSee('Sesi 1');
    }

    public function test_paket_list_shows_how_many_students_use_each_one(): void
    {
        $paketDipakai = Paket::create(['nama_paket' => 'Reguler', 'harga' => 100000, 'pertemuan' => 4]);
        $paketKosong = Paket::create(['nama_paket' => 'Privat', 'harga' => 200000, 'pertemuan' => 4]);
        Siswa::factory()->create(['paket_pembayaran' => $paketDipakai->id]);
        Siswa::factory()->create(['paket_pembayaran_3' => $paketDipakai->id]);

        $response = $this->actingAs($this->admin())->get(route('admin.workshop.index'));

        $response->assertOk()
            ->assertSee('Reguler')
            ->assertSee('Privat')
            ->assertSee('2 siswa memakai')
            ->assertSee('0 siswa memakai');
    }

    public function test_class_map_groups_existing_schedules_by_grade(): void
    {
        $this->buatKelas('12');

        $response = $this->actingAs($this->admin())->get(route('admin.workshop.index'));

        $body = $response->getContent();
        $this->assertStringContainsString('"12"', $body);
        $this->assertStringContainsString('Matematika', $body);
    }

    public function test_edit_siswa_query_param_is_passed_to_the_page(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Nayla Kirana']);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.workshop.index', ['edit_siswa' => $siswa->id]));

        $response->assertOk();
        $this->assertStringContainsString((string) $siswa->id, $response->getContent());
    }

    public function test_edit_siswa_query_param_with_unknown_id_does_not_error(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.workshop.index', ['edit_siswa' => 999999]))
            ->assertOk();
    }

    public function test_mapel_can_be_created_and_edited_through_the_shared_routes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.mapel.store'), [
            'name' => 'IPA Terpadu',
        ])->assertOk()->assertJsonPath('data.name', 'IPA Terpadu');

        $mapel = MataPelajaran::where('name', 'IPA Terpadu')->firstOrFail();

        $this->actingAs($admin)->putJson(route('admin.mapel.update', $mapel->id), [
            'name' => 'IPA Terpadu Lanjutan',
        ])->assertOk()->assertJsonPath('data.name', 'IPA Terpadu Lanjutan');

        $this->assertDatabaseHas('mata_pelajarans', ['id' => $mapel->id, 'name' => 'IPA Terpadu Lanjutan']);
    }

    public function test_guru_can_be_created_through_the_shared_route(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.guru.store'), [
            'name' => 'Pak Bagas',
        ])->assertOk()->assertJsonPath('data.name', 'Pak Bagas');

        $this->assertDatabaseHas('gurus', ['name' => 'Pak Bagas']);
    }

    public function test_ruang_can_be_created_through_the_shared_route(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.ruang.store'), [
            'name' => 'Ruang Kenanga',
        ])->assertOk()->assertJsonPath('data.name', 'Ruang Kenanga');

        $this->assertDatabaseHas('ruangs', ['name' => 'Ruang Kenanga']);
    }

    public function test_sesi_returns_the_created_record_so_the_client_can_patch_state(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('admin.sesi.store'), [
            'name' => 'Sesi 5',
            'start_time' => '20:00',
            'end_time' => '21:00',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Sesi 5');
        $this->assertDatabaseHas('sesis', ['name' => 'Sesi 5']);
    }

    public function test_siswa_can_be_created_through_the_shared_route(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.siswa.store'), [
            'name' => 'Zayn Eimar',
            'kelas' => '7',
        ])->assertOk()->assertJsonPath('data.name', 'Zayn Eimar');

        $this->assertDatabaseHas('siswas', ['name' => 'Zayn Eimar']);
    }

    public function test_siswa_phone_number_is_accepted_in_any_common_format_and_normalized_to_plus_62(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.siswa.store'), [
            'name' => 'Nadia Kirana',
            'no_hp' => '081234567890',
        ])->assertOk();

        $this->assertDatabaseHas('siswas', ['name' => 'Nadia Kirana', 'no_hp' => '+6281234567890']);
    }

    public function test_siswa_phone_number_rejects_a_genuinely_unrecognisable_value(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.siswa.store'), [
            'name' => 'Rafi Aditya',
            'no_hp' => 'bukan nomor',
        ])->assertStatus(422);
    }

    public function test_workshop_page_is_forbidden_for_guru_role(): void
    {
        $guru = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test']);
        $user = User::factory()->guru($guru)->create(['email' => $guru->email]);

        $this->actingAs($user)->get(route('admin.workshop.index'))->assertForbidden();
    }
}
