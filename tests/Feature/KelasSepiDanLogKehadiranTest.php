<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\Pertemuan;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\RaporService;
use App\Services\RingkasanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasSepiDanLogKehadiranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * @param  array<int, Siswa>  $siswa
     */
    private function kelas(string $kode, array $siswa, string $mapel = 'English'): void
    {
        $hari = Hari::firstOrCreate(['name' => 'Senin']);
        $sesi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);
        $guru = Guru::firstOrCreate(['name' => 'Bu Rina']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang Anggrek']);
        $mp = MataPelajaran::factory()->create(['name' => $mapel]);

        foreach ($siswa as $s) {
            Jadwal::create([
                'hari_id' => $hari->id,
                'sesi_id' => $sesi->id,
                'mata_pelajaran_id' => $mp->id,
                'guru_id' => $guru->id,
                'ruang_id' => $ruang->id,
                'siswa_id' => $s->id,
                'kode_kelas' => $kode,
            ]);
        }
    }

    public function test_kelas_berisi_dua_anak_dilaporkan_sepi(): void
    {
        $this->kelas('kode-1', Siswa::factory()->count(2)->create()->all());

        $sepi = app(RingkasanService::class)->kelasSepi();

        $this->assertCount(1, $sepi);
        $this->assertSame(2, $sepi->first()['jumlah_siswa']);
        $this->assertSame(1, $sepi->first()['kurang'], 'Kurang satu anak lagi untuk mencapai ambang 3.');
        $this->assertSame('English', $sepi->first()['mapel']);
        $this->assertSame('Senin', $sepi->first()['hari']);
    }

    public function test_kelas_berisi_tiga_anak_sudah_aman(): void
    {
        $this->kelas('kode-1', Siswa::factory()->count(3)->create()->all());

        $this->assertCount(0, app(RingkasanService::class)->kelasSepi(), 'Tepat tiga anak bukan lagi kelas sepi.');
    }

    public function test_kelas_sepi_muncul_di_tab_ringkasan(): void
    {
        $this->kelas('kode-1', Siswa::factory()->count(1)->create()->all());

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Kelas Sepi', false)
            ->assertSee('kurang 2 lagi', false);
    }

    public function test_panel_kelas_sepi_hilang_kalau_semua_kelas_cukup(): void
    {
        $this->kelas('kode-1', Siswa::factory()->count(4)->create()->all());

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertDontSee('kurang 1 lagi', false);
    }

    /**
     * @return array{0: Siswa, 1: ModulAjarDetail}
     */
    private function siswaDenganRiwayat(): array
    {
        $siswa = Siswa::factory()->create();
        $this->kelas('kode-1', [$siswa]);

        $modul = ModulAjar::create([
            'kode_kelas' => 'kode-1',
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);
        $detail = ModulAjarDetail::create(['modul_ajar_id' => $modul->id, 'materi' => 'Greetings']);
        $guru = Guru::where('name', 'Bu Rina')->firstOrFail();

        foreach ([['2026-03-02', true], ['2026-03-09', false], ['2026-04-06', true]] as [$tanggal, $hadir]) {
            $pertemuan = Pertemuan::create([
                'modul_ajar_detail_id' => $detail->id,
                'tanggal' => $tanggal,
                'guru_id' => $guru->id,
                'selesai_pada' => $tanggal.' 10:00:00',
            ]);

            ModulAjarAbsensi::create([
                'pertemuan_id' => $pertemuan->id,
                'siswa_id' => $siswa->id,
                'hadir' => $hadir,
                'nilai' => $hadir ? 4 : null,
            ]);
        }

        return [$siswa, $detail];
    }

    public function test_log_kehadiran_memuat_pertemuan_yang_tidak_dihadiri(): void
    {
        [$siswa] = $this->siswaDenganRiwayat();

        $log = collect(app(RaporService::class)->untukSiswa($siswa)['daftar_pertemuan']);

        $this->assertCount(3, $log, 'Pertemuan yang bolos tetap harus tercatat.');
        $this->assertSame(1, $log->where('hadir', false)->count());
        $this->assertSame('English', $log->first()['mapel']);
        $this->assertNotEmpty($log->first()['tanggal_label'], 'Tanggal harus punya label siap tampil.');
    }

    public function test_rentang_tanggal_memangkas_log_dan_ikut_menghitung_ulang_kehadiran(): void
    {
        [$siswa] = $this->siswaDenganRiwayat();

        $rapor = app(RaporService::class)->untukSiswa($siswa, '2026-03-01', '2026-03-31');

        $this->assertCount(2, $rapor['daftar_pertemuan'], 'Pertemuan April harus ikut terpangkas.');
        $this->assertSame(2, $rapor['ringkasan']['total_pertemuan']);
        $this->assertSame(1, $rapor['ringkasan']['hadir']);
        $this->assertSame(1, $rapor['ringkasan']['tidak_hadir']);
        $this->assertSame(50, $rapor['ringkasan']['persen_kehadiran'], 'Persen kehadiran dihitung dalam rentang saja.');
    }

    public function test_rentang_kosong_menghasilkan_rapor_kosong_bukan_galat(): void
    {
        [$siswa] = $this->siswaDenganRiwayat();

        $rapor = app(RaporService::class)->untukSiswa($siswa, '2026-01-01', '2026-01-31');

        $this->assertSame(0, $rapor['ringkasan']['total_pertemuan']);
        $this->assertSame([], $rapor['daftar_pertemuan']);
        $this->assertSame(0, $rapor['ringkasan']['persen_kehadiran']);
    }

    public function test_endpoint_rapor_menerima_rentang_tanggal(): void
    {
        [$siswa] = $this->siswaDenganRiwayat();

        $respon = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.result.rapor', $siswa->id).'?dari=2026-04-01&sampai=2026-04-30');

        $respon->assertOk();
        $this->assertSame(1, $respon->json('data.ringkasan.total_pertemuan'));
        $this->assertSame('2026-04-06', $respon->json('data.daftar_pertemuan.0.tanggal'));
    }
}
