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
use App\Services\IrisanSesiService;
use App\Services\RingkasanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BentrokIrisanSesiTest extends TestCase
{
    use RefreshDatabase;

    private Hari $hari;

    private Sesi $jam13;   // 13:00-14:00

    private Sesi $jam1330; // 13:30-14:30 -- bertindih dengan jam13

    private Sesi $jam14;   // 14:00-15:00 -- hanya bersentuhan di ujung dengan jam13

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->hari = Hari::create(['name' => 'Senin']);
        $this->jam13 = Sesi::factory()->create(['name' => 'SESI 01.00', 'start_time' => '13:00', 'end_time' => '14:00']);
        $this->jam1330 = Sesi::factory()->create(['name' => 'Sesi 1', 'start_time' => '13:30', 'end_time' => '14:30']);
        $this->jam14 = Sesi::factory()->create(['name' => 'SESI 02.00', 'start_time' => '14:00', 'end_time' => '15:00']);
    }

    private function irisan(): IrisanSesiService
    {
        return app(IrisanSesiService::class);
    }

    private function buatKelas(Sesi $sesi, Guru $guru, Ruang $ruang, Siswa $siswa): Jadwal
    {
        return Jadwal::create([
            'hari_id' => $this->hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_id' => $siswa->id,
            'kode_kelas' => (string) Str::uuid(),
        ]);
    }

    private function simpanKelas(Sesi $sesi, Guru $guru, Ruang $ruang, Siswa $siswa)
    {
        return $this->actingAs(User::factory()->create())->postJson(route('admin.jadwal.store'), [
            'hari_id' => $this->hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_ids' => [$siswa->id],
        ]);
    }

    public function test_sesi_yang_jamnya_bertindih_dikenali(): void
    {
        $beririsan = $this->irisan()->idBeririsan($this->jam13->id);

        $this->assertContains($this->jam13->id, $beririsan, 'Sesi itu sendiri harus ikut.');
        $this->assertContains($this->jam1330->id, $beririsan, '13:00-14:00 bertindih dengan 13:30-14:30.');
    }

    public function test_bersentuhan_tepat_di_ujung_bukan_bertindih(): void
    {
        $beririsan = $this->irisan()->idBeririsan($this->jam13->id);

        $this->assertNotContains(
            $this->jam14->id,
            $beririsan,
            '13:00-14:00 selesai persis saat 14:00-15:00 mulai, ruangnya sudah kosong.'
        );
    }

    public function test_ruang_yang_dipakai_sesi_bertindih_ditolak(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon = $this->simpanKelas($this->jam1330, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon->assertStatus(422);
        $this->assertStringContainsString('Ruang sudah digunakan', json_encode($respon->json()));
    }

    public function test_guru_yang_mengajar_di_sesi_bertindih_ditolak(): void
    {
        $guru = Guru::factory()->create();
        $this->buatKelas($this->jam13, $guru, Ruang::factory()->create(), Siswa::factory()->create());

        $respon = $this->simpanKelas($this->jam1330, $guru, Ruang::factory()->create(), Siswa::factory()->create());

        $respon->assertStatus(422);
        $this->assertStringContainsString('Guru sudah mengajar', json_encode($respon->json()));
    }

    public function test_siswa_yang_terjadwal_di_sesi_bertindih_ditolak(): void
    {
        $siswa = Siswa::factory()->create();
        $this->buatKelas($this->jam13, Guru::factory()->create(), Ruang::factory()->create(), $siswa);

        $respon = $this->simpanKelas($this->jam1330, Guru::factory()->create(), Ruang::factory()->create(), $siswa);

        $respon->assertStatus(422);
        $this->assertStringContainsString('siswa sudah punya jadwal', json_encode($respon->json()));
    }

    public function test_ruang_sama_di_sesi_yang_hanya_bersentuhan_ujung_tetap_boleh(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon = $this->simpanKelas($this->jam14, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon->assertOk();
    }

    public function test_ruang_sama_di_hari_berbeda_tetap_boleh(): void
    {
        $ruang = Ruang::factory()->create();
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $hariLain = Hari::create(['name' => 'Selasa']);
        $respon = $this->actingAs(User::factory()->create())->postJson(route('admin.jadwal.store'), [
            'hari_id' => $hariLain->id,
            'sesi_id' => $this->jam1330->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => Guru::factory()->create()->id,
            'ruang_id' => $ruang->id,
            'siswa_ids' => [Siswa::factory()->create()->id],
        ]);

        $respon->assertOk();
    }

    public function test_pesan_bentrok_menyebut_sesi_lain_yang_bertindih(): void
    {
        $ruang = Ruang::factory()->create();
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon = $this->simpanKelas($this->jam1330, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $pesan = json_encode($respon->json());
        $this->assertStringContainsString('SESI 01.00', $pesan, 'Admin harus tahu sesi mana yang bertabrakan.');
        $this->assertStringContainsString('bertindih', $pesan);
    }

    public function test_slot_kosong_di_workshop_tidak_menawarkan_ruang_yang_terpakai_sesi_bertindih(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang Z']);
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon = $this->actingAs(User::factory()->create())->get(route('admin.workshop.index'));
        $respon->assertOk();

        $ketersediaan = collect($respon->viewData('ketersediaan'));
        $slot1330 = $ketersediaan->first(fn ($s) => $s['hari'] === 'Senin' && str_contains($s['sesi'], 'Sesi 1'));

        $this->assertNotNull($slot1330);
        $this->assertNotContains(
            'Ruang A',
            $slot1330['ruang_kosong']->all(),
            'Ruang A dipakai 13:00-14:00, jadi tidak boleh ditawarkan kosong untuk 13:30-14:30.'
        );
        $this->assertContains('Ruang Z', $slot1330['ruang_kosong']->all());
    }

    public function test_slot_kosong_tetap_menawarkan_ruang_untuk_sesi_yang_hanya_bersentuhan_ujung(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $respon = $this->actingAs(User::factory()->create())->get(route('admin.workshop.index'));

        $ketersediaan = collect($respon->viewData('ketersediaan'));
        $slot14 = $ketersediaan->first(fn ($s) => $s['hari'] === 'Senin' && str_contains($s['sesi'], 'SESI 02.00'));

        $this->assertContains('Ruang A', $slot14['ruang_kosong']->all());
    }

    public function test_ringkasan_melaporkan_bentrok_lintas_sesi_yang_bertindih(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());
        $this->buatKelas($this->jam1330, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $bentrok = app(RingkasanService::class)->bentrokTersembunyi();

        $gabungan = $bentrok->implode(' | ');
        $this->assertStringContainsString('Ruang A', $gabungan);
        $this->assertStringContainsString('bertindih', $gabungan);
    }

    public function test_ringkasan_tidak_melaporkan_sesi_yang_hanya_bersentuhan_ujung(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $this->buatKelas($this->jam13, Guru::factory()->create(), $ruang, Siswa::factory()->create());
        $this->buatKelas($this->jam14, Guru::factory()->create(), $ruang, Siswa::factory()->create());

        $bentrok = app(RingkasanService::class)->bentrokTersembunyi();

        $this->assertStringNotContainsString('bertindih', $bentrok->implode(' | '));
    }

    public function test_kapasitas_slot_per_hari_tidak_menghitung_sesi_yang_bertindih(): void
    {
        $this->assertSame(2, $this->irisan()->kapasitasSlotPerHari());
    }
}
