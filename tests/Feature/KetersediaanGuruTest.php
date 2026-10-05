<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\JejakPerubahan;
use App\Models\KetersediaanGuru;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\KetersediaanGuruService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KetersediaanGuruTest extends TestCase
{
    use RefreshDatabase;

    private Guru $guru;

    private Hari $hari;

    private Sesi $sesi;

    private MataPelajaran $mapel;

    private Ruang $ruang;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->guru = Guru::create(['name' => 'Bu Rina']);
        $this->hari = Hari::create(['name' => 'Sabtu']);
        $this->sesi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '09:00', 'end_time' => '10:00']);
        $this->mapel = MataPelajaran::factory()->create(['name' => 'English']);
        $this->ruang = Ruang::factory()->create(['name' => 'Ruang Anggrek']);
        $this->siswa = Siswa::factory()->create();
    }

    private function tandaiTidakBisa(string $mulai = '08:00', string $selesai = '12:00', ?string $alasan = 'kuliah'): KetersediaanGuru
    {
        return KetersediaanGuru::create([
            'guru_id' => $this->guru->id,
            'hari_id' => $this->hari->id,
            'jam_mulai' => $mulai,
            'jam_selesai' => $selesai,
            'alasan' => $alasan,
        ]);
    }

    /**
     * @param  array<string, mixed>  $tambahan
     * @return array<string, mixed>
     */
    private function muatanJadwalBaru(array $tambahan = []): array
    {
        return array_merge([
            'hari_id' => $this->hari->id,
            'sesi_id' => $this->sesi->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'ruang_id' => $this->ruang->id,
            'siswa_ids' => [$this->siswa->id],
        ], $tambahan);
    }

    public function test_jam_yang_beririsan_dianggap_bentrok(): void
    {
        $this->tandaiTidakBisa('08:00', '09:30');

        $this->assertNotNull(
            app(KetersediaanGuruService::class)->bentrok($this->guru->id, $this->hari->id, $this->sesi->id),
            'Sesi 09:00-10:00 beririsan dengan penanda 08:00-09:30.'
        );
    }

    public function test_jam_yang_hanya_bersentuhan_di_tepi_bukan_bentrok(): void
    {
        $this->tandaiTidakBisa('07:00', '09:00');

        $this->assertNull(
            app(KetersediaanGuruService::class)->bentrok($this->guru->id, $this->hari->id, $this->sesi->id),
            'Penanda berakhir tepat saat sesi mulai, sama seperti aturan bentrok ruang.'
        );
    }

    public function test_hari_lain_tidak_terpengaruh(): void
    {
        $hariLain = Hari::create(['name' => 'Minggu']);
        $this->tandaiTidakBisa();

        $this->assertNull(
            app(KetersediaanGuruService::class)->bentrok($this->guru->id, $hariLain->id, $this->sesi->id)
        );
    }

    public function test_menyimpan_jadwal_di_jam_terlarang_ditolak_dan_minta_konfirmasi(): void
    {
        $this->tandaiTidakBisa();

        $respon = $this->actingAs(User::factory()->create())
            ->postJson(route('admin.jadwal.store'), $this->muatanJadwalBaru());

        $respon->assertStatus(409);
        $this->assertTrue($respon->json('butuh_paksa'), 'Layar perlu tahu ini bisa dipaksa.');
        $this->assertStringContainsString('Bu Rina', $respon->json('message'));
        $this->assertStringContainsString('kuliah', $respon->json('message'));
        $this->assertSame(0, Jadwal::count(), 'Belum boleh ada jadwal tersimpan.');
    }

    public function test_admin_bisa_memaksa_dan_pemaksaannya_tercatat(): void
    {
        $this->tandaiTidakBisa();

        $this->actingAs(User::factory()->create(['name' => 'Admin Utama']))
            ->postJson(route('admin.jadwal.store'), $this->muatanJadwalBaru(['paksa' => true]))
            ->assertOk();

        $this->assertSame(1, Jadwal::count(), 'Kalau dipaksa, jadwalnya memang tersimpan.');

        $jejak = JejakPerubahan::where('aksi', JejakPerubahan::AKSI_DIPAKSA)->first();
        $this->assertNotNull($jejak, 'Pemaksaan wajib meninggalkan jejak.');
        $this->assertSame('Admin Utama', $jejak->nama_pelaku);
        $this->assertStringContainsString('melanggar ketersediaan guru', $jejak->ringkasan);
    }

    public function test_tanpa_penanda_jadwal_tersimpan_seperti_biasa(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.jadwal.store'), $this->muatanJadwalBaru())
            ->assertOk();

        $this->assertSame(1, Jadwal::count());
        $this->assertSame(0, JejakPerubahan::where('aksi', JejakPerubahan::AKSI_DIPAKSA)->count());
    }

    public function test_slot_kosong_tidak_menawarkan_guru_yang_berhalangan(): void
    {
        $this->tandaiTidakBisa();

        $peta = collect($this->actingAs(User::factory()->create())
            ->get(route('admin.workshop.index'))
            ->assertOk()
            ->viewData('ketersediaan'));

        $slot = $peta->first(fn ($baris) => $baris['hari'] === 'Sabtu' && str_starts_with($baris['sesi'], 'Sesi Pagi'));

        $this->assertNotNull($slot);
        $this->assertNotContains('Bu Rina', $slot['guru_kosong']->all());
    }

    public function test_jadwal_lama_yang_melanggar_dilaporkan_di_ringkasan(): void
    {
        Jadwal::create([
            'hari_id' => $this->hari->id,
            'sesi_id' => $this->sesi->id,
            'mata_pelajaran_id' => $this->mapel->id,
            'guru_id' => $this->guru->id,
            'ruang_id' => $this->ruang->id,
            'siswa_id' => $this->siswa->id,
            'kode_kelas' => 'kode-1',
        ]);

        $this->tandaiTidakBisa();

        $langgar = app(KetersediaanGuruService::class)->jadwalYangMelanggar();

        $this->assertCount(1, $langgar);
        $this->assertSame('Bu Rina', $langgar->first()['guru']);
        $this->assertSame('kuliah', $langgar->first()['alasan']);
    }

    public function test_penanda_bisa_ditambah_dan_dihapus_lewat_layar(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->postJson(route('admin.ketersediaanGuru.store'), [
            'guru_id' => $this->guru->id,
            'hari_id' => $this->hari->id,
            'jam_mulai' => '17:00',
            'jam_selesai' => '21:00',
            'alasan' => 'kerja',
        ])->assertOk();

        $penanda = KetersediaanGuru::firstOrFail();

        $this->actingAs($admin)
            ->deleteJson(route('admin.ketersediaanGuru.destroy', $penanda->id))
            ->assertOk();

        $this->assertSame(0, KetersediaanGuru::count());
    }

    public function test_jam_selesai_harus_lewat_dari_jam_mulai(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.ketersediaanGuru.store'), [
                'guru_id' => $this->guru->id,
                'hari_id' => $this->hari->id,
                'jam_mulai' => '17:00',
                'jam_selesai' => '09:00',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.jam_selesai.0', 'Jam selesai harus lewat dari jam mulai.');
    }

    public function test_guru_tidak_boleh_mengubah_penanda(): void
    {
        $this->actingAs(User::factory()->guru(Guru::create(['name' => 'Pak Anwar']))->create())
            ->postJson(route('admin.ketersediaanGuru.store'), [
                'guru_id' => $this->guru->id,
                'hari_id' => $this->hari->id,
                'jam_mulai' => '17:00',
                'jam_selesai' => '21:00',
            ])
            ->assertStatus(403);
    }
}
