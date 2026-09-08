<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\TingkatKemampuan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaporSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function buatKelas(string $kodeKelas, Siswa $siswa, string $namaMapel = 'Matematika'): Guru
    {
        $guru = Guru::factory()->create(['name' => 'Bu Rina']);

        Jadwal::create([
            'siswa_id' => $siswa->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => $namaMapel])->id,
            'guru_id' => $guru->id,
            'hari_id' => Hari::factory()->create(['name' => 'Hari'.uniqid()])->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'sesi_id' => Sesi::factory()->create()->id,
            'kode_kelas' => $kodeKelas,
        ]);

        return $guru;
    }

    private function catatPertemuan(
        string $kodeKelas,
        Siswa $siswa,
        Guru $guru,
        string $materi,
        string $tanggal,
        bool $hadir,
        ?int $nilai
    ): ModulAjarDetail {
        $modulAjar = ModulAjar::firstOrCreate(
            ['kode_kelas' => $kodeKelas],
            ['tujuan_pembelajaran' => 'x', 'kompetensi_awal' => 'x', 'model_pembelajaran' => 'x', 'sarana_media' => 'x']
        );

        $detail = ModulAjarDetail::create([
            'modul_ajar_id' => $modulAjar->id,
            'materi' => $materi,
            'tanggal_diajarkan' => $tanggal,
            'diajarkan_oleh_guru_id' => $guru->id,
        ]);

        ModulAjarAbsensi::create([
            'modul_ajar_detail_id' => $detail->id,
            'siswa_id' => $siswa->id,
            'hadir' => $hadir,
            'nilai' => $nilai,
        ]);

        return $detail;
    }

    public function test_rapor_summarises_attendance_and_scores(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Budi']);
        $guru = $this->buatKelas('kode-1', $siswa);

        $this->catatPertemuan('kode-1', $siswa, $guru, 'Materi 1', '2026-09-01', true, 2);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'Materi 2', '2026-09-02', true, 4);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'Materi 3', '2026-09-03', false, null);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id))
            ->assertOk()
            ->json('data');

        $this->assertSame(3, $data['ringkasan']['total_pertemuan']);
        $this->assertSame(2, $data['ringkasan']['hadir']);
        $this->assertSame(1, $data['ringkasan']['tidak_hadir']);
        $this->assertSame(67, $data['ringkasan']['persen_kehadiran']);
        $this->assertEquals(3.0, $data['ringkasan']['rata_nilai']);
        $this->assertSame(2, $data['ringkasan']['nilai_terendah']);
        $this->assertSame(4, $data['ringkasan']['nilai_tertinggi']);
    }

    public function test_rapor_groups_meetings_per_mapel_and_orders_them_by_date(): void
    {
        $siswa = Siswa::factory()->create();
        $guruMtk = $this->buatKelas('kode-mtk', $siswa, 'Matematika');
        $guruIng = $this->buatKelas('kode-ing', $siswa, 'Bahasa Inggris');

        $this->catatPertemuan('kode-mtk', $siswa, $guruMtk, 'Perkalian', '2026-09-05', true, 5);
        $this->catatPertemuan('kode-mtk', $siswa, $guruMtk, 'Pembagian', '2026-09-01', true, 3);
        $this->catatPertemuan('kode-ing', $siswa, $guruIng, 'Vocabulary', '2026-09-03', true, 4);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id))
            ->assertOk()
            ->json('data');

        $this->assertCount(2, $data['per_mapel']);

        $inggris = collect($data['per_mapel'])->firstWhere('mapel', 'Bahasa Inggris');
        $matematika = collect($data['per_mapel'])->firstWhere('mapel', 'Matematika');

        $this->assertSame(1, $inggris['jumlah_pertemuan']);
        $this->assertSame(2, $matematika['jumlah_pertemuan']);
        $this->assertEquals(4.0, $matematika['rata_nilai']);
        $this->assertSame('Pembagian', $matematika['pertemuan'][0]['materi'], 'Pertemuan harus urut menurut tanggal.');
        $this->assertSame('Perkalian', $matematika['pertemuan'][1]['materi']);
    }

    public function test_rapor_can_be_filtered_by_date_range(): void
    {
        $siswa = Siswa::factory()->create();
        $guru = $this->buatKelas('kode-1', $siswa);

        $this->catatPertemuan('kode-1', $siswa, $guru, 'Lama', '2026-08-10', true, 3);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'Baru', '2026-09-10', true, 5);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id).'?dari=2026-09-01&sampai=2026-09-30')
            ->assertOk()
            ->json('data');

        $this->assertSame(1, $data['ringkasan']['total_pertemuan']);
        $this->assertSame('Baru', $data['per_mapel'][0]['pertemuan'][0]['materi']);
    }

    public function test_rapor_ignores_meetings_that_have_not_been_taught_yet(): void
    {
        $siswa = Siswa::factory()->create();
        $guru = $this->buatKelas('kode-1', $siswa);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'Sudah diajar', '2026-09-01', true, 4);

        $modulAjar = ModulAjar::where('kode_kelas', 'kode-1')->first();
        $belumDiajar = ModulAjarDetail::create(['modul_ajar_id' => $modulAjar->id, 'materi' => 'Belum diajar']);
        ModulAjarAbsensi::create([
            'modul_ajar_detail_id' => $belumDiajar->id,
            'siswa_id' => $siswa->id,
            'hadir' => true,
            'nilai' => 5,
        ]);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id))
            ->assertOk()
            ->json('data');

        $this->assertSame(1, $data['ringkasan']['total_pertemuan']);
        $this->assertEquals(4.0, $data['ringkasan']['rata_nilai']);
    }

    public function test_trend_needs_at_least_four_scores_and_detects_improvement(): void
    {
        $siswa = Siswa::factory()->create();
        $guru = $this->buatKelas('kode-1', $siswa);

        $this->catatPertemuan('kode-1', $siswa, $guru, 'M1', '2026-09-01', true, 2);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'M2', '2026-09-02', true, 2);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id))->json('data');
        $this->assertNull($data['ringkasan']['tren'], 'Tren belum boleh muncul di bawah 4 nilai.');

        $this->catatPertemuan('kode-1', $siswa, $guru, 'M3', '2026-09-03', true, 5);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'M4', '2026-09-04', true, 5);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id))->json('data');
        $this->assertSame('naik', $data['ringkasan']['tren']);
    }

    public function test_rapor_shows_the_students_kemampuan_level(): void
    {
        $kemampuan = TingkatKemampuan::create(['level' => 2, 'keterangan' => 'Menengah']);
        $siswa = Siswa::factory()->create(['tingkat_kemampuan_id' => $kemampuan->id]);
        $this->buatKelas('kode-1', $siswa);

        $data = $this->actingAs(User::factory()->create())
            ->getJson(route('admin.siswa.rapor', $siswa->id))
            ->assertOk()
            ->json('data');

        $this->assertSame('Level 2 — Menengah', $data['siswa']['kemampuan']);
    }

    public function test_rapor_pdf_downloads_successfully(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Budi Santoso']);
        $guru = $this->buatKelas('kode-1', $siswa);
        $this->catatPertemuan('kode-1', $siswa, $guru, 'Perkalian', '2026-09-01', true, 4);

        $respon = $this->actingAs(User::factory()->create())->get(route('admin.siswa.raporPdf', $siswa->id));

        $respon->assertOk();
        $this->assertSame('application/pdf', $respon->headers->get('content-type'));
    }

    public function test_guru_cannot_access_the_rapor_endpoints(): void
    {
        $siswa = Siswa::factory()->create();
        $guru = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test']);
        $userGuru = User::factory()->guru($guru)->create();

        $this->actingAs($userGuru)->getJson(route('admin.siswa.rapor', $siswa->id))->assertForbidden();
        $this->actingAs($userGuru)->get(route('admin.siswa.raporPdf', $siswa->id))->assertForbidden();
    }
}
