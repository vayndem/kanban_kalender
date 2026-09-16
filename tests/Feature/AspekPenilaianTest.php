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
use App\Models\Pertemuan;
use App\Models\RaporCetak;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PayrollService;
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

    public function test_menilai_ulang_menyimpan_nilai_tiap_pertemuan_terpisah(): void
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

        $this->assertSame(2, Pertemuan::where('modul_ajar_detail_id', $detail->id)->count(), 'Ajar ulang membuat pertemuan baru.');
        $this->assertSame(
            [2, 5],
            NilaiAspek::orderBy('id')->pluck('skor')->all(),
            'Tiap pertemuan menyimpan nilainya sendiri, jadi nilai lama tidak hilang.'
        );
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

    public function test_mengajar_ulang_menambah_kehadiran_mengajar(): void
    {
        $a = $this->aspek('Vocabulary');
        [$guru, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $nilai = fn (int $skor) => $this->actingAs($user)
            ->postJson(route('modulAjar.simpanNilai', $detail->id), [
                'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => $skor]]],
            ]);

        $nilai(3)->assertOk();
        $this->assertSame(1, AbsensiGuru::where('guru_id', $guru->id)->count());

        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $nilai(5)->assertOk();

        $this->assertSame(
            2,
            AbsensiGuru::where('guru_id', $guru->id)->count(),
            'Kelas yang diulang harus menambah satu kehadiran mengajar.'
        );
        $this->assertSame(2, NilaiAspek::count(), 'Tiap pertemuan menyimpan nilainya sendiri.');
    }

    public function test_menilai_dua_kali_tanpa_mengajar_ulang_ditolak(): void
    {
        $a = $this->aspek('Vocabulary');
        [$guru, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $kirim = fn () => $this->actingAs($user)
            ->postJson(route('modulAjar.simpanNilai', $detail->id), [
                'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
            ]);

        $kirim()->assertOk();
        $respon = $kirim();

        $respon->assertStatus(422);
        $this->assertStringContainsString('tidak sedang berlangsung', $respon->json('message'));
        $this->assertSame(
            1,
            AbsensiGuru::where('guru_id', $guru->id)->count(),
            'Kirim ganda tidak boleh menambah kehadiran, karena itu langsung jadi uang.'
        );
    }

    public function test_kehadiran_tambahan_ikut_terhitung_di_penggajian_berikutnya(): void
    {
        $a = $this->aspek('Vocabulary');
        [$guru, $user, $siswa, $detail] = $this->kelasSiapDinilai();
        $guru->update(['gaji_bawaan' => 100000, 'gaji_per_kehadiran' => 20000]);

        $nilai = fn () => $this->actingAs($user)
            ->postJson(route('modulAjar.simpanNilai', $detail->id), [
                'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
            ]);

        $nilai()->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $nilai()->assertOk();

        $baris = collect(app(PayrollService::class)->ringkasan())->firstWhere('id', $guru->id);

        $this->assertSame(2, $baris['kehadiran_belum_dibayar']);
        $this->assertSame(140000, $baris['perkiraan_total']);
    }

    public function test_cetak_rapor_wajib_memilih_pertemuan(): void
    {
        $siswa = Siswa::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.result.cetakRapor', $siswa->id), [])
            ->assertSessionHasErrors('pertemuan');
    }

    public function test_cetak_rapor_menolak_pertemuan_milik_siswa_lain(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $siswaLain = Siswa::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.result.cetakRapor', $siswaLain->id), ['pertemuan' => [Pertemuan::where('modul_ajar_detail_id', $detail->id)->value('id')]])
            ->assertSessionHasErrors('pertemuan.0');
    }

    public function test_rapor_dan_sertifikat_bisa_diunduh_untuk_pertemuan_terpilih(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $admin = User::factory()->create();

        $rapor = $this->actingAs($admin)
            ->post(route('admin.result.cetakRapor', $siswa->id), [
                'pertemuan' => [$detail->id],
                'kekuatan' => 'Sudah berani bertanya.',
            ]);
        $rapor->assertOk();
        $this->assertStringContainsString('application/pdf', $rapor->headers->get('content-type'));

        $sertifikat = $this->actingAs($admin)
            ->post(route('admin.result.cetakSertifikat', $siswa->id), ['pertemuan' => [Pertemuan::where('modul_ajar_detail_id', $detail->id)->value('id')]]);
        $sertifikat->assertOk();
        $this->assertStringContainsString('application/pdf', $sertifikat->headers->get('content-type'));
    }

    public function test_rapor_memakai_persentase_bukan_skala_lima(): void
    {
        $a = $this->aspek('Vocabulary', 1);
        $b = $this->aspek('Pronunciation', 2);
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 5, $b->id => 2]]],
        ])->assertOk();

        $rapor = app(RaporService::class)->untukSiswa($siswa->fresh(), null, null, [$detail->id]);

        $this->assertSame(100, $rapor['per_aspek'][0]['persen']);
        $this->assertSame('Excellent', $rapor['per_aspek'][0]['predikat']);
        $this->assertSame(40, $rapor['per_aspek'][1]['persen']);
        $this->assertSame('Beginning', $rapor['per_aspek'][1]['predikat']);
        $this->assertSame(70, $rapor['ringkasan']['persen']);
        $this->assertSame('Very Good', $rapor['ringkasan']['predikat']);
    }

    public function test_rapor_hanya_menghitung_pertemuan_yang_dipilih(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 5]]],
        ])->assertOk();

        $detailKedua = ModulAjarDetail::create([
            'modul_ajar_id' => $detail->modul_ajar_id,
            'materi' => 'Materi 2',
        ]);
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detailKedua->id))->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detailKedua->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 1]]],
        ])->assertOk();

        $rapor = app(RaporService::class);

        $pertama = Pertemuan::where('modul_ajar_detail_id', $detail->id)->value('id');
        $kedua = Pertemuan::where('modul_ajar_detail_id', $detailKedua->id)->value('id');

        $this->assertSame(2, $rapor->untukSiswa($siswa->fresh())['ringkasan']['total_pertemuan']);
        $this->assertSame(100, $rapor->untukSiswa($siswa->fresh(), null, null, [$pertama])['ringkasan']['persen']);
        $this->assertSame(20, $rapor->untukSiswa($siswa->fresh(), null, null, [$kedua])['ringkasan']['persen']);
    }

    public function test_menu_result_berada_tepat_setelah_pembayaran(): void
    {
        $halaman = $this->actingAs(User::factory()->create())->get(route('admin.result.index'));

        $isi = $halaman->assertOk()->getContent();
        $posisiPembayaran = strpos($isi, 'fa-wallet');
        $posisiResult = strpos($isi, 'fa-medal');
        $posisiWorkshop = strpos($isi, 'fa-toolbox');

        $this->assertNotFalse($posisiResult);
        $this->assertGreaterThan($posisiPembayaran, $posisiResult, 'Result harus di kanan Pembayaran.');
        $this->assertLessThan($posisiWorkshop, $posisiResult, 'Result harus sebelum Workshop.');
    }

    public function test_pertemuan_tercatat_bertanggal_saat_guru_mulai_ajar(): void
    {
        [$guru, $user, , $detail] = $this->kelasSiapDinilai();

        $pertemuan = Pertemuan::where('modul_ajar_detail_id', $detail->id)->firstOrFail();

        $this->assertSame(now()->toDateString(), $pertemuan->tanggal->toDateString());
        $this->assertSame($guru->id, $pertemuan->guru_id);
        $this->assertNull($pertemuan->selesai_pada, 'Pertemuan baru berstatus sedang berlangsung.');
        $this->assertTrue($detail->fresh()->sedang_dipersiapkan);
    }

    public function test_pertemuan_ditutup_setelah_dinilai(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $pertemuan = Pertemuan::where('modul_ajar_detail_id', $detail->id)->firstOrFail();

        $this->assertNotNull($pertemuan->selesai_pada);
        $this->assertFalse($detail->fresh()->sedang_dipersiapkan);
        $this->assertSame(now()->toDateString(), $detail->fresh()->tanggal_diajarkan);
    }

    public function test_dua_pertemuan_materi_sama_tercatat_terpisah_dengan_tanggalnya(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $nilai = fn (int $skor) => $this->actingAs($user)
            ->postJson(route('modulAjar.simpanNilai', $detail->id), [
                'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => $skor]]],
            ]);

        $nilai(2)->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $nilai(5)->assertOk();

        $pertemuans = Pertemuan::where('modul_ajar_detail_id', $detail->id)->orderBy('id')->get();

        $this->assertCount(2, $pertemuans);
        $this->assertTrue($pertemuans->every(fn (Pertemuan $p) => $p->selesai_pada !== null));

        $rapor = app(RaporService::class)->untukSiswa($siswa->fresh());
        $this->assertSame(2, $rapor['ringkasan']['total_pertemuan'], 'Dua kali mengajar berarti dua pertemuan.');
        $this->assertSame(70, $rapor['ringkasan']['persen'], 'Rata-rata dari kedua pertemuan, bukan hanya yang terakhir.');
    }

    public function test_rapor_bisa_dibatasi_ke_satu_pertemuan_saja(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();

        $nilai = fn (int $skor) => $this->actingAs($user)
            ->postJson(route('modulAjar.simpanNilai', $detail->id), [
                'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => $skor]]],
            ]);

        $nilai(2)->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $nilai(5)->assertOk();

        $pertemuans = Pertemuan::where('modul_ajar_detail_id', $detail->id)->orderBy('id')->pluck('id');
        $rapor = app(RaporService::class);

        $this->assertSame(40, $rapor->untukSiswa($siswa->fresh(), null, null, [$pertemuans[0]])['ringkasan']['persen']);
        $this->assertSame(100, $rapor->untukSiswa($siswa->fresh(), null, null, [$pertemuans[1]])['ringkasan']['persen']);
    }

    public function test_setiap_cetak_rapor_menyimpan_catatannya(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $pertemuan = Pertemuan::where('modul_ajar_detail_id', $detail->id)->value('id');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.result.cetakRapor', $siswa->id), [
            'pertemuan' => [$pertemuan],
            'kekuatan' => 'Cepat menangkap kosakata baru.',
            'komentar' => 'Pertahankan semangatnya.',
        ])->assertOk();

        $catatan = RaporCetak::where('siswa_id', $siswa->id)->firstOrFail();

        $this->assertSame('Cepat menangkap kosakata baru.', $catatan->kekuatan);
        $this->assertSame('Pertahankan semangatnya.', $catatan->komentar);
        $this->assertSame([$pertemuan], $catatan->pertemuan_ids);
        $this->assertSame($admin->id, $catatan->dicetak_oleh);
    }

    public function test_cetak_kedua_menyimpan_baris_baru_bukan_menimpa(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $pertemuan = Pertemuan::where('modul_ajar_detail_id', $detail->id)->value('id');
        $admin = User::factory()->create();

        foreach (['Catatan pertama', 'Catatan kedua'] as $isi) {
            $this->actingAs($admin)->post(route('admin.result.cetakRapor', $siswa->id), [
                'pertemuan' => [$pertemuan],
                'kekuatan' => $isi,
            ])->assertOk();
        }

        $this->assertSame(2, RaporCetak::where('siswa_id', $siswa->id)->count());
        $this->assertSame('Catatan kedua', RaporCetak::terakhirUntuk($siswa->id)->kekuatan);
    }

    public function test_sertifikat_tidak_menyimpan_catatan(): void
    {
        $a = $this->aspek('Vocabulary');
        [, $user, $siswa, $detail] = $this->kelasSiapDinilai();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'skor' => [$a->id => 4]]],
        ])->assertOk();

        $pertemuan = Pertemuan::where('modul_ajar_detail_id', $detail->id)->value('id');

        $this->actingAs(User::factory()->create())
            ->post(route('admin.result.cetakSertifikat', $siswa->id), ['pertemuan' => [$pertemuan]])
            ->assertOk();

        $this->assertSame(0, RaporCetak::count(), 'Sertifikat tidak perlu catatan, jadi tidak menyimpan apa pun.');
    }
}
