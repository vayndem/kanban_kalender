<?php

namespace Tests\Feature;

use App\Models\AspekPenilaian;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\JejakPerubahan;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\NilaiAspek;
use App\Models\Paket;
use App\Models\Pertemuan;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\KuotaPertemuanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JejakDanKuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_menambah_siswa_meninggalkan_jejak_beserta_pelakunya(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Admin Utama']))
            ->postJson(route('admin.siswa.store'), [
                'name' => 'Bimasena Arya',
                'kelas' => '4',
                'no_hp' => '+6281234567890',
            ])
            ->assertOk();

        $jejak = JejakPerubahan::where('entitas', JejakPerubahan::ENTITAS_SISWA)->firstOrFail();

        $this->assertSame(JejakPerubahan::AKSI_DIBUAT, $jejak->aksi);
        $this->assertSame('Admin Utama', $jejak->nama_pelaku);
        $this->assertStringContainsString('Bimasena Arya', $jejak->ringkasan);
    }

    public function test_mengubah_siswa_mencatat_nilai_sebelum_dan_sesudah(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Rafa', 'kelas' => '3']);

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.siswa.update', $siswa->id), [
                'name' => 'Rafa',
                'kelas' => '5',
                'no_hp' => $siswa->no_hp,
            ])
            ->assertOk();

        $jejak = JejakPerubahan::where('aksi', JejakPerubahan::AKSI_DIUBAH)->firstOrFail();

        $this->assertStringContainsString('Kelas: 3 → 5', $jejak->ringkasan);
        $this->assertSame('3', (string) $jejak->detail['Kelas']['dari']);
        $this->assertSame('5', (string) $jejak->detail['Kelas']['jadi']);
    }

    public function test_perubahan_yang_tidak_mengubah_apa_apa_tidak_dicatat(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Rafa', 'kelas' => '3']);

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.siswa.update', $siswa->id), [
                'name' => 'Rafa',
                'kelas' => '3',
                'no_hp' => $siswa->no_hp,
            ])
            ->assertOk();

        $this->assertSame(0, JejakPerubahan::where('aksi', JejakPerubahan::AKSI_DIUBAH)->count());
    }

    public function test_membuat_kelas_dicatat_satu_baris_bukan_satu_per_siswa(): void
    {
        $hari = Hari::create(['name' => 'Senin']);
        $sesi = Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00']);
        $mapel = MataPelajaran::factory()->create(['name' => 'English']);
        $guru = Guru::create(['name' => 'Bu Rina']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang Anggrek']);
        $siswa = Siswa::factory()->count(4)->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.jadwal.store'), [
                'hari_id' => $hari->id,
                'sesi_id' => $sesi->id,
                'mata_pelajaran_id' => $mapel->id,
                'guru_id' => $guru->id,
                'ruang_id' => $ruang->id,
                'siswa_ids' => $siswa->pluck('id')->all(),
            ])
            ->assertOk();

        $jejak = JejakPerubahan::where('entitas', JejakPerubahan::ENTITAS_JADWAL)->get();

        $this->assertCount(1, $jejak, 'Empat baris jadwal tetap satu peristiwa.');
        $this->assertStringContainsString('English', $jejak->first()->ringkasan);
        $this->assertStringContainsString('4 siswa', $jejak->first()->ringkasan);
        $this->assertSame(4, Jadwal::count());
    }

    /**
     * @return array{0: Siswa, 1: ModulAjarDetail}
     */
    private function siswaDenganPaket(int $pertemuanPaket): array
    {
        $paket = Paket::create([
            'nama_paket' => 'Paket '.$pertemuanPaket,
            'harga' => 100000,
            'pertemuan' => $pertemuanPaket,
        ]);

        $siswa = Siswa::factory()->create(['paket_pembayaran' => $paket->id]);

        $modul = ModulAjar::create([
            'kode_kelas' => 'kode-'.$siswa->id,
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        return [$siswa, ModulAjarDetail::create(['modul_ajar_id' => $modul->id, 'materi' => 'Greetings'])];
    }

    private function hadir(Siswa $siswa, ModulAjarDetail $detail, string $tanggal, bool $hadir = true): void
    {
        $pertemuan = Pertemuan::create([
            'modul_ajar_detail_id' => $detail->id,
            'tanggal' => $tanggal,
            'guru_id' => Guru::firstOrCreate(['name' => 'Bu Rina'])->id,
            'selesai_pada' => $tanggal.' 10:00:00',
        ]);

        $absensi = ModulAjarAbsensi::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'hadir' => $hadir,
        ]);

        if ($hadir) {
            NilaiAspek::create([
                'modul_ajar_absensi_id' => $absensi->id,
                'aspek_penilaian_id' => AspekPenilaian::firstOrCreate(
                    ['nama' => 'Vocabulary'],
                    ['indikator' => 'x', 'urutan' => 1]
                )->id,
                'skor' => 4,
            ]);
        }
    }

    public function test_anak_yang_kurang_dari_kuota_terdeteksi(): void
    {
        [$siswa, $detail] = $this->siswaDenganPaket(4);
        $this->hadir($siswa, $detail, '2026-03-05');

        $baris = app(KuotaPertemuanService::class)->perSiswa('2026-03')->firstWhere('siswa_id', $siswa->id);

        $this->assertSame(4, $baris['kuota']);
        $this->assertSame(1, $baris['hadir']);
        $this->assertSame(-3, $baris['selisih']);
        $this->assertSame('kurang', $baris['status']);
    }

    public function test_anak_yang_melebihi_kuota_terdeteksi(): void
    {
        [$siswa, $detail] = $this->siswaDenganPaket(2);
        foreach (['2026-03-05', '2026-03-12', '2026-03-19'] as $tanggal) {
            $this->hadir($siswa, $detail, $tanggal);
        }

        $baris = app(KuotaPertemuanService::class)->perSiswa('2026-03')->firstWhere('siswa_id', $siswa->id);

        $this->assertSame(3, $baris['hadir']);
        $this->assertSame(1, $baris['selisih']);
        $this->assertSame('lebih', $baris['status']);
    }

    public function test_ketidakhadiran_tidak_memotong_kuota(): void
    {
        [$siswa, $detail] = $this->siswaDenganPaket(2);
        $this->hadir($siswa, $detail, '2026-03-05', true);
        $this->hadir($siswa, $detail, '2026-03-12', false);

        $baris = app(KuotaPertemuanService::class)->perSiswa('2026-03')->firstWhere('siswa_id', $siswa->id);

        $this->assertSame(1, $baris['hadir'], 'Yang dihitung hanya yang benar-benar hadir.');
    }

    public function test_pertemuan_bulan_lain_tidak_ikut_dihitung(): void
    {
        [$siswa, $detail] = $this->siswaDenganPaket(2);
        $this->hadir($siswa, $detail, '2026-03-05');
        $this->hadir($siswa, $detail, '2026-04-05');

        $maret = app(KuotaPertemuanService::class)->perSiswa('2026-03')->firstWhere('siswa_id', $siswa->id);
        $april = app(KuotaPertemuanService::class)->perSiswa('2026-04')->firstWhere('siswa_id', $siswa->id);

        $this->assertSame(1, $maret['hadir']);
        $this->assertSame(1, $april['hadir']);
    }

    public function test_kuota_menjumlahkan_semua_paket_yang_dimiliki(): void
    {
        $a = Paket::create(['nama_paket' => 'A', 'harga' => 1, 'pertemuan' => 4]);
        $b = Paket::create(['nama_paket' => 'B', 'harga' => 1, 'pertemuan' => 3]);

        $siswa = Siswa::factory()->create([
            'paket_pembayaran' => $a->id,
            'paket_pembayaran_2' => $b->id,
        ]);

        $baris = app(KuotaPertemuanService::class)->perSiswa('2026-03')->firstWhere('siswa_id', $siswa->id);

        $this->assertSame(7, $baris['kuota']);
    }

    public function test_siswa_tanpa_paket_tidak_ikut_dihitung_kurang(): void
    {
        Siswa::factory()->create();

        $ringkasan = app(KuotaPertemuanService::class)->ringkasan('2026-03');

        $this->assertSame(0, $ringkasan['kurang']->count());
        $this->assertSame(1, $ringkasan['tanpa_paket']);
    }

    public function test_panel_kuota_muncul_di_tab_ringkasan(): void
    {
        $this->siswaDenganPaket(4);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Kuota Paket vs Kehadiran', false)
            ->assertSee('Pertemuan dibayar', false);
    }

    public function test_panel_jejak_muncul_di_tab_ringkasan(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Utama']);

        $this->actingAs($admin)->postJson(route('admin.siswa.store'), [
            'name' => 'Anak Baru',
            'kelas' => '4',
            'no_hp' => '+6281234567890',
        ])->assertOk();

        $this->actingAs($admin)
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Jejak Perubahan Terakhir', false)
            ->assertSee('Anak Baru', false);
    }
}
