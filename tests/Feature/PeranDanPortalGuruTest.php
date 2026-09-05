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
use Tests\TestCase;

class PeranDanPortalGuruTest extends TestCase
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

    private function guruDenganAkun(string $nama = 'Bu Rina'): array
    {
        $guru = Guru::create(['name' => $nama, 'email' => strtolower(str_replace(' ', '.', $nama)).'@eling.test']);
        $user = User::factory()->guru($guru)->create([
            'name' => $nama,
            'email' => $guru->email,
        ]);

        return [$guru, $user];
    }

    private function buatKelas(Guru $guru, Siswa $siswa): Jadwal
    {
        return Jadwal::create([
            'siswa_id' => $siswa->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => 'English Class'])->id,
            'guru_id' => $guru->id,
            'hari_id' => Hari::factory()->create(['name' => 'Senin'])->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'sesi_id' => Sesi::factory()->create()->id,
        ]);
    }

    public function test_guru_cannot_reach_any_admin_page(): void
    {
        [, $user] = $this->guruDenganAkun();

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.akunGuru.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.workshop.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.arsip.index'))->assertForbidden();
    }

    public function test_guru_cannot_perform_any_write_action(): void
    {
        [, $user] = $this->guruDenganAkun();
        $siswa = Siswa::factory()->create();

        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), [
            'id_siswa' => $siswa->id,
            'harga' => 100000,
            'keterangan' => 'Percobaan',
        ])->assertForbidden();

        $this->actingAs($user)->postJson(route('admin.pembayaran.penagihanMassal'))->assertForbidden();
        $this->actingAs($user)->deleteJson(route('admin.siswa.destroy', $siswa->id))->assertForbidden();

        $this->assertDatabaseCount('pembayarans', 0);
        $this->assertDatabaseHas('siswas', ['id' => $siswa->id]);
    }

    public function test_guru_only_sees_their_own_schedule(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        [$guruLain] = $this->guruDenganAkun('Pak Anwar');

        $siswaSaya = Siswa::factory()->create(['name' => 'Nayla Kirana']);
        $siswaOrangLain = Siswa::factory()->create(['name' => 'Rafa Ardhito']);

        $this->buatKelas($guru, $siswaSaya);
        $this->buatKelas($guruLain, $siswaOrangLain);

        $response = $this->actingAs($user)->get(route('guru.jadwal'));

        $response->assertOk()
            ->assertSee('Nayla Kirana')
            ->assertDontSee('Rafa Ardhito');
    }

    public function test_admin_cannot_reach_the_teacher_portal(): void
    {
        $this->actingAs($this->admin())->get(route('guru.jadwal'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('guru.jadwal'))->assertRedirect(route('login'));
        $this->get(route('admin.akunGuru.index'))->assertRedirect(route('login'));
        $this->get(route('admin.workshop.index'))->assertRedirect(route('login'));
    }

    public function test_teacher_account_without_linked_guru_gets_an_explanation(): void
    {
        $user = User::factory()->guru()->create();

        $this->actingAs($user)->get(route('guru.jadwal'))
            ->assertOk()
            ->assertSee('belum tertaut', false);
    }

    public function test_login_sends_each_role_to_its_own_landing_page(): void
    {
        [, $guruUser] = $this->guruDenganAkun();

        $this->post(route('login'), [
            'email' => $guruUser->email,
            'password' => 'password',
        ])->assertRedirect(route('guru.jadwal', absolute: false));

        $this->post(route('logout'));

        $admin = $this->admin();
        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_existing_accounts_are_treated_as_admin_after_migration(): void
    {
        $lama = User::factory()->tanpaPeran()->create(['guru_id' => null]);
        $this->assertFalse($lama->hasRole(User::ROLE_ADMIN));

        $this->seed(RoleSeeder::class);

        $this->assertTrue($lama->fresh()->hasRole(User::ROLE_ADMIN));
    }
}
