<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarDetail;
use App\Models\Pertemuan;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\RingkasanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelasPenggantiRingkasanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function kelas(string $kode, string $namaGuru, string $mapel): ModulAjarDetail
    {
        $guru = Guru::create(['name' => $namaGuru]);

        Jadwal::create([
            'hari_id' => Hari::firstOrCreate(['name' => 'Senin'])->id,
            'sesi_id' => Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00'])->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => $mapel])->id,
            'guru_id' => $guru->id,
            'ruang_id' => Ruang::factory()->create(['name' => 'Ruang Anggrek'])->id,
            'siswa_id' => Siswa::factory()->create()->id,
            'kode_kelas' => $kode,
        ]);

        $modul = ModulAjar::create([
            'kode_kelas' => $kode,
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        return ModulAjarDetail::create(['modul_ajar_id' => $modul->id, 'materi' => 'Greetings']);
    }

    public function test_tidak_menampilkan_apa_apa_saat_semua_guru_hadir(): void
    {
        $this->kelas('kode-1', 'Bu Rina', 'English');

        $hasil = app(RingkasanService::class)->kelasPengganti();

        $this->assertCount(0, $hasil['slot_terbuka']);
        $this->assertCount(0, $hasil['sedang_diajar_pengganti']);
    }

    public function test_slot_terbuka_menyebut_kelas_guru_asli_dan_materinya(): void
    {
        $detail = $this->kelas('kode-1', 'Bu Rina', 'English');
        $detail->update(['tidak_bisa_hadir' => true]);

        $item = app(RingkasanService::class)->kelasPengganti()['slot_terbuka']->first();

        $this->assertSame('English', $item['mapel']);
        $this->assertSame('Bu Rina', $item['guru_asli']);
        $this->assertSame('Senin', $item['hari']);
        $this->assertSame('Ruang Anggrek', $item['ruang']);
        $this->assertSame('Greetings', $item['materi']);
        $this->assertSame(1, $item['jumlah_siswa']);
        $this->assertFalse($item['ajar_ulang'], 'Materi ini belum pernah diajarkan.');
    }

    public function test_slot_ajar_ulang_ditandai_terpisah(): void
    {
        $detail = $this->kelas('kode-1', 'Bu Rina', 'English');
        Pertemuan::create([
            'modul_ajar_detail_id' => $detail->id,
            'tanggal' => now()->subWeek()->toDateString(),
            'guru_id' => Guru::first()->id,
            'selesai_pada' => now()->subWeek(),
        ]);
        $detail->update(['tidak_bisa_hadir' => true]);

        $item = app(RingkasanService::class)->kelasPengganti()['slot_terbuka']->first();

        $this->assertTrue($item['ajar_ulang'], 'Materi yang sudah pernah diajarkan harus ditandai sesi ajar ulang.');
    }

    public function test_kelas_yang_sudah_diambil_pindah_ke_daftar_pengganti(): void
    {
        $detail = $this->kelas('kode-1', 'Bu Rina', 'English');
        $pengganti = Guru::create(['name' => 'Pak Anwar']);

        Pertemuan::create([
            'modul_ajar_detail_id' => $detail->id,
            'tanggal' => now()->toDateString(),
            'guru_id' => $pengganti->id,
            'guru_pengganti_id' => $pengganti->id,
        ]);

        $hasil = app(RingkasanService::class)->kelasPengganti();

        $this->assertCount(0, $hasil['slot_terbuka'], 'Yang sudah diambil tidak boleh terhitung menggantung.');

        $item = $hasil['sedang_diajar_pengganti']->first();
        $this->assertSame('Pak Anwar', $item['pengganti']);
        $this->assertSame('Bu Rina', $item['guru_asli']);
        $this->assertSame('English', $item['mapel']);
    }

    public function test_pertemuan_yang_sudah_selesai_tidak_lagi_dilaporkan(): void
    {
        $detail = $this->kelas('kode-1', 'Bu Rina', 'English');
        $pengganti = Guru::create(['name' => 'Pak Anwar']);

        Pertemuan::create([
            'modul_ajar_detail_id' => $detail->id,
            'tanggal' => now()->toDateString(),
            'guru_id' => $pengganti->id,
            'guru_pengganti_id' => $pengganti->id,
            'selesai_pada' => now(),
        ]);

        $hasil = app(RingkasanService::class)->kelasPengganti();

        $this->assertCount(0, $hasil['sedang_diajar_pengganti'], 'Kelas yang sudah selesai diajar bukan lagi urusan berjalan.');
    }

    public function test_panel_muncul_di_tab_ringkasan(): void
    {
        $detail = $this->kelas('kode-1', 'Bu Rina', 'English');
        $detail->update(['tidak_bisa_hadir' => true]);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Belum ada yang ambil')
            ->assertSee('Sudah diambil guru lain');
    }

    public function test_panel_tidak_muncul_kalau_tidak_ada_apa_apa(): void
    {
        $this->kelas('kode-1', 'Bu Rina', 'English');

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertDontSee('Belum ada yang ambil')
            ->assertDontSee('Sudah diambil guru lain');
    }
}
