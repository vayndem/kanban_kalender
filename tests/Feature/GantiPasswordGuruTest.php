<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GantiPasswordGuruTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: Guru, 1: User}
     */
    private function guruBerakun(string $nama = 'Bu Rina'): array
    {
        $guru = Guru::create(['name' => $nama, 'email' => 'rina@eling.test']);
        $user = User::factory()->guru($guru)->create([
            'name' => $nama,
            'email' => 'rina@eling.test',
            'password' => Hash::make('lamaSekali123'),
        ]);

        return [$guru, $user];
    }

    public function test_admin_bisa_mengganti_password_guru_tanpa_tahu_password_lamanya(): void
    {
        [$guru, $user] = $this->guruBerakun();

        $respon = $this->actingAs(User::factory()->create())
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
                'password' => 'rahasiaBaru123',
                'password_confirmation' => 'rahasiaBaru123',
            ]);

        $respon->assertOk();
        $this->assertStringContainsString('berhasil diganti', $respon->json('message'));
        $this->assertTrue(Hash::check('rahasiaBaru123', $user->fresh()->password));
        $this->assertFalse(Hash::check('lamaSekali123', $user->fresh()->password));
    }

    public function test_guru_tidak_bisa_mengganti_password_guru_lain(): void
    {
        [$guru] = $this->guruBerakun();
        $penyusup = User::factory()->guru(Guru::create(['name' => 'Pak Anwar']))->create();

        $this->actingAs($penyusup)
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
                'password' => 'rahasiaBaru123',
                'password_confirmation' => 'rahasiaBaru123',
            ])
            ->assertStatus(403);
    }

    public function test_tamu_ditolak(): void
    {
        [$guru] = $this->guruBerakun();

        $this->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
            'password' => 'rahasiaBaru123',
            'password_confirmation' => 'rahasiaBaru123',
        ])->assertStatus(401);
    }

    public function test_ketikan_ulang_yang_tidak_sama_ditolak(): void
    {
        [$guru, $user] = $this->guruBerakun();

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
                'password' => 'rahasiaBaru123',
                'password_confirmation' => 'salahKetik123',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Ketikan ulang password belum sama.');

        $this->assertTrue(Hash::check('lamaSekali123', $user->fresh()->password), 'Password lama harus tetap berlaku.');
    }

    public function test_password_pendek_ditolak(): void
    {
        [$guru] = $this->guruBerakun();

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password baru minimal 8 karakter.');
    }

    public function test_guru_tanpa_akun_ditolak_dengan_penjelasan(): void
    {
        $guru = Guru::create(['name' => 'Pak Yusuf']);

        $respon = $this->actingAs(User::factory()->create())
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
                'password' => 'rahasiaBaru123',
                'password_confirmation' => 'rahasiaBaru123',
            ]);

        $respon->assertStatus(422);
        $this->assertStringContainsString('belum punya akun login', $respon->json('message'));
    }

    public function test_guru_yang_tidak_ada_dijawab_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', 999999), [
                'password' => 'rahasiaBaru123',
                'password_confirmation' => 'rahasiaBaru123',
            ])
            ->assertStatus(404);
    }

    public function test_sesi_login_guru_diputus_setelah_passwordnya_diganti(): void
    {
        [$guru, $user] = $this->guruBerakun();

        config(['session.driver' => 'database']);

        DB::table('sessions')->insert([
            'id' => 'sesi-guru-lama',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'uji',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.akunGuru.ubahPasswordGuru', $guru->id), [
                'password' => 'rahasiaBaru123',
                'password_confirmation' => 'rahasiaBaru123',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-guru-lama']);
    }

    public function test_tombol_password_hanya_muncul_untuk_guru_yang_punya_akun(): void
    {
        $this->guruBerakun();
        Guru::create(['name' => 'Pak Yusuf']);

        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('admin.akunGuru.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            2,
            substr_count($halaman, 'gantiPassword('),
            'Tombolnya muncul sekali di tabel dan sekali di kartu, hanya untuk guru berakun.'
        );
    }
}
