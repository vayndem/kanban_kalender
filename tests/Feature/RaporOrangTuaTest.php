<?php

namespace Tests\Feature;

use App\Models\AspekPenilaian;
use App\Models\Guru;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\NilaiAspek;
use App\Models\Pertemuan;
use App\Models\RaporCetak;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RaporOrangTuaTest extends TestCase
{
    use RefreshDatabase;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        RateLimiter::clear('rapor-ortu:127.0.0.1');

        $this->siswa = Siswa::factory()->create([
            'name' => 'Abdullah Abbas',
            'kelas' => '5',
            'no_hp' => '+628510217 5588',
        ]);
    }

    private function beriNilai(int $skor = 4): Pertemuan
    {
        $aspek = AspekPenilaian::create([
            'nama' => 'Vocabulary Mastery',
            'indikator' => 'Mengenal dan menggunakan vocabulary sesuai materi level',
            'urutan' => 1,
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
            'guru_id' => Guru::create(['name' => 'Bu Rina'])->id,
            'selesai_pada' => now(),
        ]);

        $absensi = ModulAjarAbsensi::create([
            'pertemuan_id' => $pertemuan->id,
            'siswa_id' => $this->siswa->id,
            'hadir' => true,
        ]);

        NilaiAspek::create([
            'modul_ajar_absensi_id' => $absensi->id,
            'aspek_penilaian_id' => $aspek->id,
            'skor' => $skor,
        ]);

        return $pertemuan;
    }

    public function test_halaman_bisa_dibuka_tanpa_login(): void
    {
        $this->get(route('rapor.publik'))
            ->assertOk()
            ->assertSee('Rapor Perkembangan Anak', false)
            ->assertSee('4 Angka Terakhir HP', false);
    }

    public function test_tautannya_muncul_di_halaman_depan(): void
    {
        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee(route('rapor.publik'), false)
            ->assertSee('Rapor Anak Saya', false);
    }

    public function test_nama_dan_empat_digit_benar_menampilkan_rapor(): void
    {
        $this->beriNilai(4);

        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])
            ->assertOk()
            ->assertSee('Abdullah Abbas', false)
            ->assertSee('Vocabulary Mastery', false)
            ->assertSee('80%', false);
    }

    public function test_nama_boleh_beda_besar_kecil_huruf_dan_spasi(): void
    {
        $this->beriNilai();

        $this->post(route('rapor.publik.cari'), ['nama' => '  abdullah ABBAS  ', 'empat_digit' => '5588'])
            ->assertOk()
            ->assertSee('Abdullah Abbas', false);
    }

    public function test_empat_digit_salah_ditolak(): void
    {
        $this->beriNilai();

        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '0000'])
            ->assertSessionHasErrors('nama')
            ->assertRedirect();
    }

    public function test_nama_yang_tidak_ada_ditolak_dengan_pesan_yang_sama(): void
    {
        $this->beriNilai();

        $this->post(route('rapor.publik.cari'), ['nama' => 'Anak Tidak Ada', 'empat_digit' => '5588'])
            ->assertSessionHasErrors('nama');
    }

    public function test_empat_digit_wajib_berupa_empat_angka(): void
    {
        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '55'])
            ->assertSessionHasErrors('empat_digit');
    }

    public function test_percobaan_beruntun_dibatasi(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post(route('rapor.publik.cari'), ['nama' => 'Tebak '.$i, 'empat_digit' => '1111']);
        }

        $this->post(route('rapor.publik.cari'), ['nama' => 'Tebak lagi', 'empat_digit' => '1111'])
            ->assertSessionHasErrors('nama');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('nama')
        );
    }

    public function test_tebakan_benar_mengosongkan_hitungan_percobaan(): void
    {
        $this->beriNilai();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('rapor.publik.cari'), ['nama' => 'Salah '.$i, 'empat_digit' => '1111']);
        }

        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])->assertOk();
        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])->assertOk();
    }

    public function test_catatan_guru_dari_cetakan_terakhir_ikut_tampil(): void
    {
        $pertemuan = $this->beriNilai();

        RaporCetak::create([
            'siswa_id' => $this->siswa->id,
            'dicetak_oleh' => User::factory()->create()->id,
            'periode_label' => now()->translatedFormat('F Y'),
            'pertemuan_ids' => [$pertemuan->id],
            'kekuatan' => 'Sudah berani memulai percakapan.',
            'perbaikan' => 'Masih perlu latihan pengucapan.',
        ]);

        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])
            ->assertOk()
            ->assertSee('Sudah berani memulai percakapan.', false)
            ->assertSee('Masih perlu latihan pengucapan.', false);
    }

    public function test_tanpa_cetakan_bagian_catatan_tidak_muncul(): void
    {
        $this->beriNilai();

        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])
            ->assertOk()
            ->assertDontSee('Catatan Guru', false);
    }

    public function test_anak_yang_belum_pernah_dinilai_tetap_aman_dibuka(): void
    {
        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])
            ->assertOk()
            ->assertSee('Belum ada pertemuan yang dinilai', false);
    }

    public function test_halaman_publik_tidak_membocorkan_daftar_anak_lain(): void
    {
        Siswa::factory()->create(['name' => 'Anak Lain Sekali', 'no_hp' => '+628111111111']);
        $this->beriNilai();

        $this->post(route('rapor.publik.cari'), ['nama' => 'Abdullah Abbas', 'empat_digit' => '5588'])
            ->assertOk()
            ->assertDontSee('Anak Lain Sekali', false);
    }
}
