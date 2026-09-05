<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarDetail;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ModulAjarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function guruDenganAkun(string $nama = 'Bu Rina'): array
    {
        $guru = Guru::create(['name' => $nama, 'email' => strtolower(str_replace(' ', '.', $nama)) . '@eling.test']);
        $user = User::factory()->guru($guru)->create(['name' => $nama, 'email' => $guru->email]);

        return [$guru, $user];
    }

    private function buatKelas(Guru $guru, string $kodeKelas, array $overrides = []): Jadwal
    {
        return Jadwal::create(array_merge([
            'siswa_id' => Siswa::factory()->create()->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'hari_id' => Hari::factory()->create(['name' => 'Senin' . uniqid()])->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'sesi_id' => Sesi::factory()->create()->id,
            'kode_kelas' => $kodeKelas,
        ], $overrides));
    }

    public function test_admin_sees_all_classes_but_guru_sees_only_their_own(): void
    {
        [$guruSaya, $userSaya] = $this->guruDenganAkun('Bu Rina');
        [$guruLain] = $this->guruDenganAkun('Pak Anwar');

        MataPelajaran::factory()->create(['name' => 'Kelas Saya'])->id;
        $this->buatKelas($guruSaya, 'kode-saya', ['mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => 'Kelas Saya'])->id]);
        $this->buatKelas($guruLain, 'kode-lain', ['mata_pelajaran_id' => MataPelajaran::factory()->create(['name' => 'Kelas Lain'])->id]);

        $responGuru = $this->actingAs($userSaya)->get(route('modulAjar.index'));
        $responGuru->assertOk()->assertSee('Kelas Saya')->assertDontSee('Kelas Lain');

        $responAdmin = $this->actingAs($this->admin())->get(route('modulAjar.index'));
        $responAdmin->assertOk()->assertSee('Kelas Saya')->assertSee('Kelas Lain');
    }

    public function test_a_substitute_guru_can_see_the_class_they_are_covering_in_their_own_list(): void
    {
        [$guruAsli] = $this->guruDenganAkun('Bu Rina');
        [$guruPengganti, $userPengganti] = $this->guruDenganAkun('Bu Sekar');
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guruAsli, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');
        $detail->update(['guru_pengganti_id' => $guruPengganti->id]);

        $this->actingAs($userPengganti)->get(route('modulAjar.index'))
            ->assertOk()
            ->assertSee('Penjumlahan');
    }

    public function test_header_requires_all_four_fields(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $this->buatKelas($guru, 'kode-1');

        $this->actingAs($user)->postJson(route('modulAjar.simpanHeader', 'kode-1'), [
            'tujuan_pembelajaran' => 'Belajar penjumlahan',
        ])->assertStatus(422);

        $this->assertDatabaseCount('modul_ajars', 0);
    }

    public function test_guru_can_create_header_for_own_class_but_not_edit_it_afterward(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $this->buatKelas($guru, 'kode-1');

        $payload = [
            'tujuan_pembelajaran' => 'Belajar penjumlahan',
            'kompetensi_awal' => 'Sudah kenal angka 1-10',
            'model_pembelajaran' => 'Tatap muka individual',
            'sarana_media' => 'Papan tulis dan buku latihan',
        ];

        $this->actingAs($user)->postJson(route('modulAjar.simpanHeader', 'kode-1'), $payload)->assertOk();
        $this->assertDatabaseCount('modul_ajars', 1);

        $this->actingAs($user)->postJson(route('modulAjar.simpanHeader', 'kode-1'), array_merge($payload, [
            'tujuan_pembelajaran' => 'Coba ubah',
        ]))->assertStatus(403);

        $this->assertSame('Belajar penjumlahan', ModulAjar::first()->tujuan_pembelajaran);
    }

    public function test_admin_can_update_an_existing_header(): void
    {
        [$guru] = $this->guruDenganAkun();
        $this->buatKelas($guru, 'kode-1');
        ModulAjar::create([
            'kode_kelas' => 'kode-1',
            'tujuan_pembelajaran' => 'Lama',
            'kompetensi_awal' => 'Lama',
            'model_pembelajaran' => 'Lama',
            'sarana_media' => 'Lama',
        ]);

        $this->actingAs($this->admin())->postJson(route('modulAjar.simpanHeader', 'kode-1'), [
            'tujuan_pembelajaran' => 'Baru',
            'kompetensi_awal' => 'Baru',
            'model_pembelajaran' => 'Baru',
            'sarana_media' => 'Baru',
        ])->assertOk();

        $this->assertSame('Baru', ModulAjar::first()->tujuan_pembelajaran);
    }

    public function test_guru_cannot_manage_header_of_a_class_they_do_not_teach(): void
    {
        [$guruSaya, $userSaya] = $this->guruDenganAkun('Bu Rina');
        [$guruLain] = $this->guruDenganAkun('Pak Anwar');
        $this->buatKelas($guruLain, 'kode-lain');

        $this->actingAs($userSaya)->postJson(route('modulAjar.simpanHeader', 'kode-lain'), [
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ])->assertStatus(403);
    }

    public function test_guru_can_add_detail_but_cannot_update_or_delete_it(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $this->buatKelas($guru, 'kode-1');
        $modulAjar = ModulAjar::create([
            'kode_kelas' => 'kode-1',
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        $respon = $this->actingAs($user)->postJson(route('modulAjar.simpanDetail', $modulAjar->id), [
            'materi' => 'Penjumlahan 1 digit',
        ]);
        $respon->assertOk();
        $detailId = $respon->json('data.id');

        $this->actingAs($user)->putJson(route('modulAjar.updateDetail', $detailId), [
            'materi' => 'Coba ubah',
        ])->assertStatus(403);

        $this->actingAs($user)->deleteJson(route('modulAjar.hapusDetail', $detailId))->assertStatus(403);

        $this->assertDatabaseHas('modul_ajar_details', ['id' => $detailId, 'materi' => 'Penjumlahan 1 digit']);
    }

    public function test_admin_can_update_and_delete_a_detail(): void
    {
        [$guru] = $this->guruDenganAkun();
        $this->buatKelas($guru, 'kode-1');
        $modulAjar = ModulAjar::create([
            'kode_kelas' => 'kode-1',
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);
        $detail = ModulAjarDetail::create(['modul_ajar_id' => $modulAjar->id, 'materi' => 'Awal']);

        $admin = $this->admin();
        $this->actingAs($admin)->putJson(route('modulAjar.updateDetail', $detail->id), ['materi' => 'Sudah diubah'])->assertOk();
        $this->assertSame('Sudah diubah', $detail->fresh()->materi);

        $this->actingAs($admin)->deleteJson(route('modulAjar.hapusDetail', $detail->id))->assertOk();
        $this->assertDatabaseMissing('modul_ajar_details', ['id' => $detail->id]);
    }

    public function test_detail_requires_materi(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $this->buatKelas($guru, 'kode-1');
        $modulAjar = ModulAjar::create([
            'kode_kelas' => 'kode-1',
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        $this->actingAs($user)->postJson(route('modulAjar.simpanDetail', $modulAjar->id), [])->assertStatus(422);
    }

    public function test_kode_kelas_survives_a_drag_move_in_jadwal_pelajaran(): void
    {
        $admin = $this->admin();
        $guru = Guru::factory()->create();
        $ruang = Ruang::factory()->create();
        $mapel = MataPelajaran::factory()->create();
        $hariAsal = Hari::factory()->create(['name' => 'HariAsal' . uniqid()]);
        $hariTujuan = Hari::factory()->create(['name' => 'HariTujuan' . uniqid()]);
        $sesiAsal = Sesi::factory()->create();
        $sesiTujuan = Sesi::factory()->create();
        $siswa = Siswa::factory()->create();

        $this->actingAs($admin)->postJson(route('admin.jadwal.store'), [
            'hari_id' => $hariAsal->id,
            'sesi_id' => $sesiAsal->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_ids' => [$siswa->id],
        ])->assertOk();

        $kodeAwal = Jadwal::first()->kode_kelas;
        $this->assertNotNull($kodeAwal);

        ModulAjar::create([
            'kode_kelas' => $kodeAwal,
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        $this->actingAs($admin)->postJson(route('admin.jadwal.updatePosisi'), [
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'old_hari_id' => $hariAsal->id,
            'old_sesi_id' => $sesiAsal->id,
            'new_hari_id' => $hariTujuan->id,
            'new_sesi_id' => $sesiTujuan->id,
        ])->assertOk();

        $jadwalPindah = Jadwal::first();
        $this->assertSame($hariTujuan->id, $jadwalPindah->hari_id);
        $this->assertSame($kodeAwal, $jadwalPindah->kode_kelas);
        $this->assertDatabaseHas('modul_ajars', ['kode_kelas' => $kodeAwal]);
    }

    public function test_kode_kelas_survives_editing_the_class_via_update_kelas(): void
    {
        $admin = $this->admin();
        $guru = Guru::factory()->create();
        $ruang = Ruang::factory()->create();
        $mapel = MataPelajaran::factory()->create();
        $hari = Hari::factory()->create(['name' => 'Hari' . uniqid()]);
        $sesi = Sesi::factory()->create();
        $siswaLama = Siswa::factory()->create();
        $siswaBaru = Siswa::factory()->create();

        $kodeKelas = 'kode-tetap';
        $this->buatKelas($guru, $kodeKelas, [
            'ruang_id' => $ruang->id,
            'mata_pelajaran_id' => $mapel->id,
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'siswa_id' => $siswaLama->id,
        ]);

        ModulAjar::create([
            'kode_kelas' => $kodeKelas,
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        $this->actingAs($admin)->postJson(route('admin.jadwal.updateKelas'), [
            'old_mapel_id' => $mapel->id,
            'old_guru_id' => $guru->id,
            'old_ruang_id' => $ruang->id,
            'old_hari_id' => $hari->id,
            'old_sesi_id' => $sesi->id,
            'mapel_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_ids' => [$siswaBaru->id],
        ])->assertOk();

        $jadwalBaru = Jadwal::first();
        $this->assertSame($siswaBaru->id, $jadwalBaru->siswa_id);
        $this->assertSame($kodeKelas, $jadwalBaru->kode_kelas);
        $this->assertDatabaseHas('modul_ajars', ['kode_kelas' => $kodeKelas]);
    }

    public function test_kode_kelas_survives_a_stash_download_and_upload_round_trip(): void
    {
        $admin = $this->admin();
        $guru = Guru::factory()->create();
        $this->buatKelas($guru, 'kode-asli');

        $unduhan = $this->actingAs($admin)->get(route('admin.jadwal.downloadStash'));
        $unduhan->assertOk();

        $file = UploadedFile::fake()->createWithContent('backup.stash', $unduhan->getContent());
        $this->actingAs($admin)->postJson(route('admin.jadwal.uploadStash'), ['file_stash' => $file])->assertOk();

        $this->assertSame('kode-asli', Jadwal::first()->kode_kelas);
    }

    public function test_uploading_a_legacy_stash_without_kode_kelas_still_backfills_a_stable_code_per_class(): void
    {
        $admin = $this->admin();
        $guru = Guru::factory()->create();
        $ruang = Ruang::factory()->create();
        $mapel = MataPelajaran::factory()->create();
        $hari = Hari::factory()->create(['name' => 'Hari' . uniqid()]);
        $sesi = Sesi::factory()->create();
        $siswaA = Siswa::factory()->create();
        $siswaB = Siswa::factory()->create();

        $legacyStash = base64_encode(json_encode([
            'app' => 'E-Ling-Course',
            'version' => '1.0',
            'timestamp' => now()->toDateTimeString(),
            'content' => [
                ['h' => $hari->id, 's' => $sesi->id, 'm' => $mapel->id, 'g' => $guru->id, 'r' => $ruang->id, 'si' => $siswaA->id],
                ['h' => $hari->id, 's' => $sesi->id, 'm' => $mapel->id, 'g' => $guru->id, 'r' => $ruang->id, 'si' => $siswaB->id],
            ],
        ]));

        $file = UploadedFile::fake()->createWithContent('legacy.stash', $legacyStash);
        $this->actingAs($admin)->postJson(route('admin.jadwal.uploadStash'), ['file_stash' => $file])->assertOk();

        $kodeKelas = Jadwal::pluck('kode_kelas')->unique();
        $this->assertCount(1, $kodeKelas, 'Dua siswa di kelas yang sama harus dapat kode_kelas yang sama.');
        $this->assertNotNull($kodeKelas->first());
    }

    private function buatKelasDenganSiswa(Guru $guru, string $kodeKelas, array $siswaIds): void
    {
        $bersama = [
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'hari_id' => Hari::factory()->create(['name' => 'Hari' . uniqid()])->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'sesi_id' => Sesi::factory()->create()->id,
            'kode_kelas' => $kodeKelas,
        ];

        foreach ($siswaIds as $siswaId) {
            Jadwal::create(array_merge($bersama, ['siswa_id' => $siswaId]));
        }
    }

    private function buatModulDenganDetail(string $kodeKelas): ModulAjarDetail
    {
        $modulAjar = ModulAjar::create([
            'kode_kelas' => $kodeKelas,
            'tujuan_pembelajaran' => 'x',
            'kompetensi_awal' => 'x',
            'model_pembelajaran' => 'x',
            'sarana_media' => 'x',
        ]);

        return ModulAjarDetail::create(['modul_ajar_id' => $modulAjar->id, 'materi' => 'Penjumlahan']);
    }

    public function test_mulai_persiapan_marks_the_detail_and_can_only_be_started_by_the_real_teacher_or_admin(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        [, $userLain] = $this->guruDenganAkun('Pak Anwar');
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');

        $this->actingAs($userLain)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertStatus(403);

        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $this->assertTrue($detail->fresh()->sedang_dipersiapkan);
    }

    public function test_grading_credits_attendance_to_the_real_teacher_and_completes_the_detail(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $siswaHadir = Siswa::factory()->create();
        $siswaTidakHadir = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswaHadir->id, $siswaTidakHadir->id]);
        $detail = $this->buatModulDenganDetail('kode-1');

        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();

        $respon = $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [
                ['siswa_id' => $siswaHadir->id, 'hadir' => true, 'nilai' => 4],
                ['siswa_id' => $siswaTidakHadir->id, 'hadir' => false, 'nilai' => null],
            ],
        ]);
        $respon->assertOk();

        $detail->refresh();
        $this->assertFalse($detail->sedang_dipersiapkan);
        $this->assertSame($guru->id, $detail->diajarkan_oleh_guru_id);
        $this->assertNotNull($detail->tanggal_diajarkan);

        $this->assertDatabaseHas('modul_ajar_absensis', ['siswa_id' => $siswaHadir->id, 'hadir' => 1, 'nilai' => 4]);
        $this->assertDatabaseHas('modul_ajar_absensis', ['siswa_id' => $siswaTidakHadir->id, 'hadir' => 0, 'nilai' => null]);
        $this->assertDatabaseHas('absensi_gurus', ['guru_id' => $guru->id, 'modul_ajar_detail_id' => $detail->id]);
    }

    public function test_grading_requires_nilai_when_a_student_is_marked_present(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'nilai' => null]],
        ])->assertStatus(422);
    }

    public function test_grading_rejects_a_student_who_is_not_in_the_class_roster(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $siswaKelas = Siswa::factory()->create();
        $siswaLuar = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswaKelas->id]);
        $detail = $this->buatModulDenganDetail('kode-1');
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();

        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswaLuar->id, 'hadir' => true, 'nilai' => 5]],
        ])->assertStatus(422);
    }

    public function test_substitute_teacher_can_grade_and_gets_attendance_credit_then_slot_reverts_automatically(): void
    {
        [$guruAsli, $userAsli] = $this->guruDenganAkun('Bu Rina');
        [$guruPengganti, $userPengganti] = $this->guruDenganAkun('Bu Sekar');
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guruAsli, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');

        // Guru asli menunjuk pengganti saat persiapan.
        $this->actingAs($userAsli)->postJson(route('modulAjar.mulaiPersiapan', $detail->id), [
            'guru_pengganti_id' => $guruPengganti->id,
        ])->assertOk();
        $this->assertSame($guruPengganti->id, $detail->fresh()->guru_pengganti_id);

        // Guru pengganti belum ditugaskan di jadwal ini sama sekali, tapi tetap boleh menilai
        // karena sedang jadi guru_pengganti untuk detail ini.
        $this->actingAs($userPengganti)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'nilai' => 5]],
        ])->assertOk();

        $detail->refresh();
        $this->assertNull($detail->guru_pengganti_id, 'Guru pengganti harus otomatis lepas setelah dinilai.');
        $this->assertSame($guruPengganti->id, $detail->diajarkan_oleh_guru_id, 'Absen harus dikreditkan ke guru yang benar-benar mengajar.');
        $this->assertSame($guruAsli->id, Jadwal::where('kode_kelas', 'kode-1')->value('guru_id'), 'Jadwal asli tidak boleh berubah sama sekali.');
        $this->assertDatabaseHas('absensi_gurus', ['guru_id' => $guruPengganti->id, 'modul_ajar_detail_id' => $detail->id]);
    }

    public function test_reteaching_a_detail_overwrites_the_previous_grades_instead_of_keeping_history(): void
    {
        [$guru, $user] = $this->guruDenganAkun();
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');

        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'nilai' => 2]],
        ])->assertOk();

        // Ajar ulang: buka persiapan lagi, lalu nilai ulang dengan nilai berbeda.
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'nilai' => 5]],
        ])->assertOk();

        $this->assertDatabaseCount('modul_ajar_absensis', 1);
        $this->assertDatabaseHas('modul_ajar_absensis', ['siswa_id' => $siswa->id, 'nilai' => 5]);
        $this->assertDatabaseCount('absensi_gurus', 1);
    }

    public function test_unrelated_guru_cannot_grade_a_detail_they_have_no_role_in(): void
    {
        [$guru] = $this->guruDenganAkun('Bu Rina');
        [, $userLain] = $this->guruDenganAkun('Pak Anwar');
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');

        $this->actingAs($userLain)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'nilai' => 3]],
        ])->assertStatus(403);
    }

    public function test_admin_can_see_the_current_month_attendance_recap(): void
    {
        [$guru, $user] = $this->guruDenganAkun('Bu Rina');
        $siswa = Siswa::factory()->create();
        $this->buatKelasDenganSiswa($guru, 'kode-1', [$siswa->id]);
        $detail = $this->buatModulDenganDetail('kode-1');
        $this->actingAs($user)->postJson(route('modulAjar.mulaiPersiapan', $detail->id))->assertOk();
        $this->actingAs($user)->postJson(route('modulAjar.simpanNilai', $detail->id), [
            'absensi' => [['siswa_id' => $siswa->id, 'hadir' => true, 'nilai' => 3]],
        ])->assertOk();

        $this->actingAs($this->admin())->get(route('modulAjar.index'))
            ->assertOk()
            ->assertSee('Bu Rina')
            ->assertSee('1 sesi');

        $this->actingAs($user)->get(route('modulAjar.index'))
            ->assertOk()
            ->assertSee('Sudah mengajar 1 sesi bulan ini');
    }
}
