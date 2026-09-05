<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AkunGuruTest extends TestCase
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

    public function test_existing_teachers_may_have_no_email(): void
    {
        $guru = Guru::create(['name' => 'Bu Sekar']);

        $this->assertNull($guru->email);
        $this->assertFalse($guru->punyaAkun());
        $this->assertFalse($guru->siapDibuatkanAkun());

        $this->actingAs($this->admin())->get(route('admin.akunGuru.index'))
            ->assertOk()
            ->assertSee('Bu Sekar');
    }

    public function test_admin_fills_the_email_then_creates_the_login(): void
    {
        $guru = Guru::create(['name' => 'Bu Sekar']);
        $admin = $this->admin();

        $this->actingAs($admin)->putJson(route('admin.akunGuru.ubahEmailGuru', $guru->id), [
            'email' => 'sekar@eling.test',
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertTrue($guru->fresh()->siapDibuatkanAkun());

        $this->actingAs($admin)->postJson(route('admin.akunGuru.buatAkunGuru', $guru->id), [
            'email' => 'sekar@eling.test',
            'password' => 'rahasia123',
        ])->assertOk()->assertJsonPath('status', 'success');

        $user = User::where('email', 'sekar@eling.test')->first();
        $this->assertNotNull($user);
        $this->assertSame($guru->id, $user->guru_id);
        $this->assertTrue($user->hasRole(User::ROLE_GURU));
        $this->assertFalse($user->hasRole(User::ROLE_ADMIN));
    }

    public function test_a_teacher_cannot_be_given_two_accounts(): void
    {
        $guru = Guru::create(['name' => 'Pak Anwar', 'email' => 'anwar@eling.test']);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.akunGuru.buatAkunGuru', $guru->id), [
            'email' => 'anwar@eling.test',
            'password' => 'rahasia123',
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('admin.akunGuru.buatAkunGuru', $guru->id), [
            'email' => 'anwar2@eling.test',
            'password' => 'rahasia123',
        ])->assertStatus(422);

        $this->assertSame(1, User::where('guru_id', $guru->id)->count());
    }

    public function test_email_already_used_by_another_teacher_is_rejected(): void
    {
        Guru::create(['name' => 'Bu Rina', 'email' => 'sama@eling.test']);
        $lain = Guru::create(['name' => 'Bu Melati']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.akunGuru.ubahEmailGuru', $lain->id), ['email' => 'sama@eling.test'])
            ->assertStatus(422);

        $this->assertNull($lain->fresh()->email);
    }

    public function test_email_already_used_by_another_login_account_is_rejected(): void
    {
        // Beda dari test di atas: di sini yang bentrok bukan gurus.email milik guru
        // lain, tapi email login akun lain (mis. admin) yang tidak pernah muncul
        // di tabel gurus sama sekali.
        User::factory()->create(['email' => 'admin.lain@eling.test']);
        $guru = Guru::create(['name' => 'Bu Melati']);

        $this->actingAs($this->admin())
            ->putJson(route('admin.akunGuru.ubahEmailGuru', $guru->id), ['email' => 'admin.lain@eling.test'])
            ->assertStatus(422);

        $this->assertNull($guru->fresh()->email);
    }

    public function test_changing_email_keeps_the_login_account_in_sync(): void
    {
        $guru = Guru::create(['name' => 'Bu Kirana', 'email' => 'kirana@eling.test']);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.akunGuru.buatAkunGuru', $guru->id), [
            'email' => 'kirana@eling.test',
            'password' => 'rahasia123',
        ])->assertOk();

        $this->actingAs($admin)->putJson(route('admin.akunGuru.ubahEmailGuru', $guru->id), [
            'email' => 'kirana.baru@eling.test',
        ])->assertOk();

        $this->assertSame('kirana.baru@eling.test', $guru->fresh()->email);
        $this->assertSame('kirana.baru@eling.test', User::where('guru_id', $guru->id)->value('email'));
    }

    public function test_email_cannot_be_emptied_once_it_is_used_for_login(): void
    {
        $guru = Guru::create(['name' => 'Pak Bagas', 'email' => 'bagas@eling.test']);
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.akunGuru.buatAkunGuru', $guru->id), [
            'email' => 'bagas@eling.test',
            'password' => 'rahasia123',
        ])->assertOk();

        $this->actingAs($admin)->putJson(route('admin.akunGuru.ubahEmailGuru', $guru->id), [
            'email' => null,
        ])->assertStatus(422);

        $this->assertSame('bagas@eling.test', $guru->fresh()->email);
    }

    public function test_page_does_not_expose_general_reference_data_crud(): void
    {
        Guru::create(['name' => 'Bu Sekar']);

        $this->actingAs($this->admin())->get(route('admin.akunGuru.index'))
            ->assertOk()
            ->assertDontSee('Slot Kosong per Hari')
            ->assertDontSee('Tambah Mata Pelajaran');
    }
}
