<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\AspekPenilaian;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\NilaiAspek;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Pertemuan;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PenjagaanHapusBerantaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * @return array{0: ModulAjarDetail, 1: Siswa, 2: Guru}
     */
    private function materiYangSudahDiajar(): array
    {
        $guru = Guru::create(['name' => 'Bu Rina']);
        $siswa = Siswa::factory()->create();

        Jadwal::create([
            'hari_id' => Hari::create(['name' => 'Senin'])->id,
            'sesi_id' => Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00'])->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'siswa_id' => $siswa->id,
            'kode_kelas' => 'kode-1',
        ]);

        $modul = ModulAjar::create([
            'kode_kelas' => 'kode-1',
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        $detail = ModulAjarDetail::create(['modul_ajar_id' => $modul->id, 'materi' => 'Greetings']);

        $pertemuan = Pertemuan::create([
            'modul_ajar_detail_id' => $detail->id,
            'tanggal' => now()->toDateString(),
            'guru_id' => $guru->id,
            'selesai_pada' => now(),
        ]);

        $absensi = ModulAjarAbsensi::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $siswa->id,
            'hadir' => true,
        ]);

        NilaiAspek::create([
            'modul_ajar_absensi_id' => $absensi->id,
            'aspek_penilaian_id' => AspekPenilaian::create([
                'nama' => 'Vocabulary',
                'indikator' => 'x',
                'urutan' => 1,
            ])->id,
            'skor' => 4,
        ]);

        AbsensiGuru::create([
            'guru_id' => $guru->id,
            'pertemuan_id' => $pertemuan->id,
            'tanggal' => now()->toDateString(),
        ]);

        return [$detail, $siswa, $guru];
    }

    public function test_materi_yang_sudah_diajarkan_tidak_bisa_dihapus(): void
    {
        [$detail] = $this->materiYangSudahDiajar();

        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('modulAjar.hapusDetail', $detail->id));

        $respon->assertStatus(422);
        $this->assertStringContainsString('tidak dihapus', $respon->json('message'), 'Pesan harus menyatakan permintaannya ditolak.');
        $this->assertStringContainsString('pernah diajarkan', $respon->json('message'));
    }

    public function test_menghapus_materi_tidak_boleh_ikut_melenyapkan_nilai_dan_kehadiran(): void
    {
        [$detail] = $this->materiYangSudahDiajar();

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('modulAjar.hapusDetail', $detail->id))
            ->assertStatus(422);

        $this->assertSame(1, Pertemuan::count(), 'Pertemuan harus selamat.');
        $this->assertSame(1, ModulAjarAbsensi::count(), 'Nilai anak harus selamat.');
        $this->assertSame(1, NilaiAspek::count(), 'Skor per aspek harus selamat.');
        $this->assertSame(1, AbsensiGuru::count(), 'Kehadiran mengajar guru harus selamat.');
    }

    public function test_materi_yang_sedang_diajarkan_juga_ditolak(): void
    {
        [$detail] = $this->materiYangSudahDiajar();
        Pertemuan::query()->update(['selesai_pada' => null]);

        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('modulAjar.hapusDetail', $detail->id));

        $respon->assertStatus(422);
        $this->assertStringContainsString('tidak dihapus', $respon->json('message'), 'Pesan harus menyatakan permintaannya ditolak.');
        $this->assertStringContainsString('sedang diajarkan', $respon->json('message'));
    }

    public function test_materi_yang_belum_pernah_diajarkan_tetap_boleh_dihapus(): void
    {
        [$detail] = $this->materiYangSudahDiajar();
        Pertemuan::query()->delete();

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('modulAjar.hapusDetail', $detail->id))
            ->assertOk();

        $this->assertSame(0, ModulAjarDetail::count());
    }

    public function test_tagihan_yang_sudah_ada_pembayarannya_tidak_bisa_dihapus(): void
    {
        $siswa = Siswa::factory()->create();
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'harga' => 300000,
            'total_sudah_dibayar' => 200000,
            'status' => 1,
            'keterangan' => 'Tagihan September',
        ]);

        PembayaranDetail::create([
            'id_pembayaran' => $tagihan->id,
            'pembayaran' => 200000,
            'keterangan' => 'Cicilan pertama',
        ]);

        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.pembayaran.destroy', $tagihan->id));

        $respon->assertStatus(422);
        $this->assertStringContainsString('bukti uang yang sudah diterima', $respon->json('message'));
        $this->assertSame(1, Pembayaran::count(), 'Tagihan tidak boleh hilang.');
        $this->assertSame(1, PembayaranDetail::count(), 'Catatan pembayaran tidak boleh hilang.');
    }

    public function test_tagihan_tanpa_pembayaran_boleh_dihapus_dan_diarsipkan(): void
    {
        $siswa = Siswa::factory()->create();
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'harga' => 300000,
            'total_sudah_dibayar' => 0,
            'status' => 0,
            'keterangan' => 'Salah buat',
        ]);

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.pembayaran.destroy', $tagihan->id))
            ->assertOk();

        $this->assertSame(0, Pembayaran::count());

        $arsip = DB::table('koreksi_pembayaran_logs')->where('id_pembayaran_dibuang', $tagihan->id)->first();
        $this->assertNotNull($arsip, 'Tagihan yang dihapus harus punya jejak arsip.');
        $this->assertSame(300000, (int) $arsip->nilai_tagihan_dibuang);
        $this->assertStringContainsString('Salah buat', $arsip->data_asli);
    }

    public function test_guru_tidak_bisa_menghapus_materi(): void
    {
        [$detail] = $this->materiYangSudahDiajar();
        $user = User::factory()->guru(Guru::create(['name' => 'Pak Budi']))->create();

        $this->actingAs($user)
            ->deleteJson(route('modulAjar.hapusDetail', $detail->id))
            ->assertStatus(403);
    }

    public function test_materi_yang_sudah_tidak_ada_dijawab_pesan_yang_bisa_dipahami(): void
    {
        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('modulAjar.hapusDetail', 999999));

        $respon->assertStatus(404);
        $this->assertStringContainsString('sudah tidak ada', $respon->json('message'));
        $this->assertSame('error', $respon->json('status'));
    }

    public function test_tagihan_yang_sudah_tidak_ada_juga_dijawab_rapi(): void
    {
        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.pembayaran.destroy', 999999));

        $respon->assertStatus(404);
        $this->assertSame('error', $respon->json('status'));
    }

    public function test_guru_yang_menembak_rute_admin_dapat_pesan_bukan_halaman_kosong(): void
    {
        $user = User::factory()->guru(Guru::create(['name' => 'Pak Budi']))->create();

        $respon = $this->actingAs($user)->getJson(route('admin.result.index'));

        $respon->assertStatus(403);
        $this->assertStringContainsString('tidak punya akses', $respon->json('message'));
    }
}
