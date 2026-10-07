<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\KetersediaanGuru;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\SlotKosongService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlotKosongHariIniTest extends TestCase
{
    use RefreshDatabase;

    private Hari $hariIni;

    private Hari $hariLain;

    private Sesi $pagi;

    private Sesi $siang;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Carbon::setTestNow('2026-10-05 09:00:00');

        $this->hariIni = Hari::create(['name' => 'Senin']);
        $this->hariLain = Hari::create(['name' => 'Selasa']);

        $this->pagi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);
        $this->siang = Sesi::factory()->create(['name' => 'Sesi Siang', 'start_time' => '13:00', 'end_time' => '14:00']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function kelas(Hari $hari, Sesi $sesi, Guru $guru, Ruang $ruang): void
    {
        Jadwal::create([
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_id' => Siswa::factory()->create()->id,
            'kode_kelas' => 'kode-'.$sesi->id.'-'.$ruang->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sesi(string $nama): array
    {
        $hasil = app(SlotKosongService::class)->hariIni();

        return collect($hasil['sesi'])->firstWhere('nama', $nama);
    }

    public function test_hanya_menghitung_hari_ini(): void
    {
        $guru = Guru::create(['name' => 'Bu Rina']);
        $ruangA = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariLain, $this->pagi, $guru, $ruangA);

        $hasil = app(SlotKosongService::class)->hariIni();

        $this->assertSame('Senin', $hasil['hari']);
        $this->assertSame(0, $this->sesi('Sesi Pagi')['kelas_berjalan'], 'Kelas hari Selasa tidak boleh ikut terhitung.');
        $this->assertSame(2, $this->sesi('Sesi Pagi')['ruang_kosong']->count());
    }

    public function test_ruang_yang_terpakai_tidak_lagi_disebut_kosong(): void
    {
        $guru = Guru::create(['name' => 'Bu Rina']);
        Guru::create(['name' => 'Pak Anwar']);
        $ruangA = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariIni, $this->pagi, $guru, $ruangA);

        $pagi = $this->sesi('Sesi Pagi');

        $this->assertSame(['Ruang B'], $pagi['ruang_kosong']->all());
        $this->assertSame(1, $pagi['ruang_terpakai']);
        $this->assertSame(1, $pagi['kelas_berjalan']);
        $this->assertSame('longgar', $pagi['status']);
    }

    public function test_sesi_tanpa_kelas_ditandai_kosong(): void
    {
        Ruang::factory()->create(['name' => 'Ruang A']);
        Guru::create(['name' => 'Bu Rina']);

        $this->assertSame('kosong', $this->sesi('Sesi Siang')['status']);
    }

    public function test_semua_ruang_terpakai_ditandai_penuh(): void
    {
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        $guru = Guru::create(['name' => 'Bu Rina']);

        $this->kelas($this->hariIni, $this->pagi, $guru, $ruang);

        $pagi = $this->sesi('Sesi Pagi');

        $this->assertSame('penuh', $pagi['status']);
        $this->assertSame(100, $pagi['persen_ruang_terpakai']);
        $this->assertTrue($pagi['ruang_kosong']->isEmpty());
    }

    public function test_ruang_sisa_tapi_guru_habis_tetap_dianggap_tidak_bisa_diisi(): void
    {
        $rina = Guru::create(['name' => 'Bu Rina']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariIni, $this->pagi, $rina, $ruang);

        $pagi = $this->sesi('Sesi Pagi');

        $this->assertSame(1, $pagi['ruang_kosong']->count(), 'Masih ada ruang sisa.');
        $this->assertSame(0, $pagi['guru_kosong']->count(), 'Tapi gurunya cuma satu dan sedang mengajar.');
        $this->assertSame('penuh', $pagi['status'], 'Ruang kosong tanpa guru tetap tidak bisa diisi.');
    }

    public function test_sesi_tanpa_kelas_tapi_ruangnya_dipakai_sesi_bertindih_ditandai(): void
    {
        $tumpang = Sesi::factory()->create(['name' => 'Sesi Tumpang', 'start_time' => '08:30', 'end_time' => '09:30']);
        $rina = Guru::create(['name' => 'Bu Rina']);
        Guru::create(['name' => 'Pak Anwar']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariIni, $this->pagi, $rina, $ruang);

        $hasil = collect(app(SlotKosongService::class)->hariIni()['sesi'])->firstWhere('nama', $tumpang->name);

        $this->assertSame(0, $hasil['kelas_berjalan'], 'Tidak ada kelas yang mulai di sesi ini.');
        $this->assertSame(1, $hasil['ruang_terpakai'], 'Tapi satu ruang sedang dipakai.');
        $this->assertTrue(
            $hasil['ruang_dipakai_sesi_lain'],
            'Penanda ini yang dipakai layar untuk menjelaskan kenapa angkanya terlihat bertentangan.'
        );
    }

    public function test_guru_yang_sedang_mengajar_tidak_disebut_siap(): void
    {
        $rina = Guru::create(['name' => 'Bu Rina']);
        Guru::create(['name' => 'Pak Anwar']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariIni, $this->pagi, $rina, $ruang);

        $this->assertSame(['Pak Anwar'], $this->sesi('Sesi Pagi')['guru_kosong']->all());
    }

    public function test_guru_yang_ditandai_tidak_bisa_juga_dikeluarkan(): void
    {
        $rina = Guru::create(['name' => 'Bu Rina']);
        Guru::create(['name' => 'Pak Anwar']);
        Ruang::factory()->create(['name' => 'Ruang A']);

        KetersediaanGuru::create([
            'guru_id' => $rina->id,
            'hari_id' => $this->hariIni->id,
            'jam_mulai' => '07:00',
            'jam_selesai' => '12:00',
            'alasan' => 'kuliah',
        ]);

        $pagi = $this->sesi('Sesi Pagi');

        $this->assertSame(['Pak Anwar'], $pagi['guru_kosong']->all());
        $this->assertSame(1, $pagi['guru_berhalangan']);
    }

    public function test_sesi_yang_jamnya_beririsan_ikut_menutup_ruang(): void
    {
        $tumpang = Sesi::factory()->create(['name' => 'Sesi Tumpang', 'start_time' => '08:30', 'end_time' => '09:30']);
        $guru = Guru::create(['name' => 'Bu Rina']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariIni, $this->pagi, $guru, $ruang);

        $hasil = collect(app(SlotKosongService::class)->hariIni()['sesi'])->firstWhere('nama', $tumpang->name);

        $this->assertSame(
            ['Ruang B'],
            $hasil['ruang_kosong']->all(),
            'Ruang A dipakai 08:00-09:00, jadi tidak kosong untuk sesi 08:30-09:30.'
        );
    }

    public function test_hari_yang_tidak_terdaftar_tidak_bikin_galat(): void
    {
        Hari::query()->delete();

        $hasil = app(SlotKosongService::class)->hariIni();

        $this->assertFalse($hasil['ada']);
        $this->assertCount(0, $hasil['sesi']);
    }

    public function test_panel_muncul_di_tab_ringkasan(): void
    {
        Ruang::factory()->create(['name' => 'Ruang Anggrek']);
        Guru::create(['name' => 'Bu Rina']);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Slot Kosong Hari Ini', false)
            ->assertSee('Ruang Anggrek', false)
            ->assertSee('Guru siap', false);
    }

    public function test_workshop_tetap_memakai_perhitungan_yang_sama(): void
    {
        $guru = Guru::create(['name' => 'Bu Rina']);
        $ruangA = Ruang::factory()->create(['name' => 'Ruang A']);
        Ruang::factory()->create(['name' => 'Ruang B']);

        $this->kelas($this->hariIni, $this->pagi, $guru, $ruangA);

        $peta = collect($this->actingAs(User::factory()->create())
            ->get(route('admin.workshop.index'))
            ->assertOk()
            ->viewData('ketersediaan'));

        $baris = $peta->first(fn ($x) => $x['hari'] === 'Senin' && str_starts_with($x['sesi'], 'Sesi Pagi'));

        $this->assertSame(['Ruang B'], $baris['ruang_kosong']->all());
        $this->assertSame(1, $baris['kelas_berjalan']);
    }
}
