<?php

namespace Tests\Feature;

use App\Models\AspekPenilaian;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\NilaiAspek;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\RaporService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AspekPenilaianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function aspek(string $nama, int $urutan = 1, bool $aktif = true): AspekPenilaian
    {
        return AspekPenilaian::create([
            'nama' => $nama,
            'indikator' => 'Indikator untuk '.$nama,
            'urutan' => $urutan,
            'aktif' => $aktif,
        ]);
    }

    /**
     * @return array{0: Guru, 1: User, 2: Siswa, 3: ModulAjarDetail}
     */
    private function kelasSiapDinilai(): array
    {
        $guru = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test']);
        $user = User::factory()->guru($guru)->create();
        $siswa = Siswa::factory()->create(['name' => 'Abbas']);

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

        $detail = ModulAjarDetail::create(['modul_ajar_id' => $modul->id, 'materi' => 'Materi 1']);
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();

        return [$guru, $user, $siswa, $detail];
    }

    public function test_admin_bisa_menambah_aspek_dan_indikatornya(): void
    {
        $respon = $this->actingAs(User::factory()->create())
            ->postJson(route('admin.result.simpanAspek'), [
                'nama' => 'Vocabulary Mastery',
                'indikator' => 'Mengenal dan menggunakan vocabulary sesuai materi level',
            ]);

        $respon->assertOk();
        $this->assertDatabaseHas('aspek_penilaians', [
            'nama' => 'Vocabulary Mastery',
            'indikator' => 'Mengenal dan menggunakan vocabulary sesuai materi level',
            'aktif' => 1,
        ]);
    }

    public function test_nama_aspek_tidak_boleh_kembar(): void
    {
        $this->aspek('Pronunciation');

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.result.simpanAspek'), [
                'nama' => 'Pronunciation',
                'indikator' => 'Kejelasan pengucapan',
            ])
            ->assertStatus(422);

        $this->assertSame(1, AspekPenilaian::count());
    }

    public function test_guru_tidak_boleh_mengatur_aspek(): void
    {
        $user = User::factory()->guru(Guru::create(['name' => 'Pak Budi']))->create();

        $this->actingAs($user)->get(route('admin.result.index'))->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('admin.result.simpanAspek'), ['nama' => 'X', 'indikator' => 'Y'])
            ->assertForbidden();
    }

    public function test_penilaian_menyimpan_skor_untuk_setiap_aspek(): void
    {
        $a = $this->aspek('Vocabulary', 1);
        $b = $this->aspek('Pronunciation', 2);
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [
                ['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4, $b->id => 2]],
            ],
        ])->assertOk();

        $absensi = ModulAjarAbsensi::where('siswa_id', $siswa->id)->firstOrFail();
        $this->assertSame(2, $absensi->nilaiAspeks()->count());
        $this->assertSame(4, NilaiAspek::where('aspek_penilaian_id', $a->id)->value('skor'));
        $this->assertSame(2, NilaiAspek::where('aspek_penilaian_id', $b->id)->value('skor'));
        $this->assertSame(3.0, $absensi->fresh()->rataAspek());
    }

    public function test_penilaian_ditolak_kalau_ada_aspek_yang_belum_diisi(): void
    {
        $a = $this->aspek('Vocabulary', 1);
        $this->aspek('Pronunciation', 2);
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $respon = $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [
                ['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]],
            ],
        ]);

        $respon->assertStatus(422);
        $this->assertStringContainsString('Pronunciation', $respon->json('message'));
        $this->assertSame(0, NilaiAspek::count(), 'Tidak boleh ada nilai tersimpan separuh.');
    }

    public function test_aspek_nonaktif_tidak_wajib_diisi(): void
    {
        $aktif = $this->aspek('Vocabulary', 1);
        $mati = $this->aspek('Pronunciation', 2, false);
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [
                ['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$aktif->id => 5]],
            ],
        ])->assertOk();

        $this->assertSame(0, NilaiAspek::where('aspek_penilaian_id', $mati->id)->count());
    }

    public function test_anak_tidak_hadir_tidak_perlu_dinilai(): void
    {
        $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [
                ['siswa_id' => $siswa->id, 'hadir' => false, 'skor' => []],
            ],
        ])->assertOk();

        $this->assertSame(0, NilaiAspek::count());
    }

    public function test_penilaian_ditolak_kalau_admin_belum_menentukan_aspek(): void
    {
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $respon = $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => []]],
        ]);

        $respon->assertStatus(422);
        $this->assertStringContainsString('Aspek penilaian belum ditentukan', $respon->json('message'));
    }

    public function test_menilai_ulang_menimpa_skor_lama_bukan_menumpuk(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $kirim = fn (int $skor) => $this->actingAs($user)
            ->postJson(route('modulAjar.simpanNilai', $detail->id), [
                'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => $skor]]],
            ]);

        $kirim(2)->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $kirim(5)->assertOk();

        $this->assertSame(1, NilaiAspek::count());
        $this->assertSame(5, NilaiAspek::first()->skor);
    }

    public function test_aspek_yang_sudah_dipakai_tidak_bisa_dihapus(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 3]]],
        ])->assertOk();

        $respon = $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.result.hapusAspek', $a->id));

        $respon->assertStatus(422);
        $this->assertStringContainsString('Nonaktifkan', $respon->json('message'));
        $this->assertDatabaseHas('aspek_penilaians', ['id' => $a->id]);
    }

    public function test_aspek_yang_belum_dipakai_boleh_dihapus(): void
    {
        $a = $this->aspek('Belum Dipakai');

        $this->actingAs(User::factory()->create())
            ->deleteJson(route('admin.result.hapusAspek', $a->id))
            ->assertOk();

        $this->assertDatabaseMissing('aspek_penilaians', ['id' => $a->id]);
    }

    public function test_menonaktifkan_aspek_tidak_menghapus_rapor_lama(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.result.ubahAspek', $a->id), [
                'nama' => $a->nama,
                'indikator' => $a->indikator,
                'aktif' => false,
            ])->assertOk();

        $rapor = app(RaporService::class)->untukSiswa($siswa->fresh());

        $this->assertSame(4.0, $rapor['ringkasan']['rata_nilai']);
        $this->assertCount(1, $rapor['per_aspek']);
        $this->assertSame('Vocabulary', $rapor['per_aspek'][0]['nama']);
    }

    public function test_rapor_merinci_rata_rata_tiap_aspek(): void
    {
        $a = $this->aspek('Vocabulary', 1);
        $b = $this->aspek('Pronunciation', 2);
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 5, $b->id => 1]]],
        ])->assertOk();

        $rapor = app(RaporService::class)->untukSiswa($siswa->fresh());

        $this->assertSame(3.0, $rapor['ringkasan']['rata_nilai'], 'Nilai pertemuan adalah rata-rata semua aspek.');
        $this->assertSame('Vocabulary', $rapor['per_aspek'][0]['nama']);
        $this->assertSame(5.0, $rapor['per_aspek'][0]['rata']);
        $this->assertSame('Pronunciation', $rapor['per_aspek'][1]['nama']);
        $this->assertSame(1.0, $rapor['per_aspek'][1]['rata']);
    }

    public function test_halaman_result_menampilkan_aspek_dan_kartu_anak(): void
    {
        $this->aspek('Vocabulary');
        Siswa::factory()->create(['name' => 'Abbas']);

        $halaman = $this->actingAs(User::factory()->create())->get(route('admin.result.index'));

        $halaman->assertOk();
        $halaman->assertSee('Aspek Penilaian', false);
        $halaman->assertSee('Hasil Anak', false);
        $halaman->assertSee('Vocabulary', false);
        $halaman->assertSee('Abbas', false);
    }

    public function test_rapor_perkembangan_tidak_lagi_ada_di_data_siswa(): void
    {
        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'data_siswa']));

        $halaman->assertOk();
        $halaman->assertDontSee('Rapor Perkembangan', false);
        $halaman->assertDontSee('Download Rapor PDF', false);
    }

    public function test_menu_result_muncul_dengan_ikon_medali(): void
    {
        $halaman = $this->actingAs(User::factory()->create())->get(route('admin.result.index'));

        $halaman->assertOk();
        $halaman->assertSee('fa-medal', false);
        $halaman->assertSee(route('admin.result.index'), false);
    }
}
