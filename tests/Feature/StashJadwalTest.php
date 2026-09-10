<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\StashPemulihanLog;
use App\Models\User;
use App\Services\StashJadwalService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Tests\TestCase;

class StashJadwalTest extends TestCase
{
    use RefreshDatabase;

    private Hari $hari;

    private Sesi $sesi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->hari = Hari::create(['name' => 'Senin']);
        $this->sesi = Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00']);
    }

    private function buatJadwal(int $jumlah = 2): void
    {
        for ($i = 0; $i < $jumlah; $i++) {
            Jadwal::create([
                'hari_id' => $this->hari->id,
                'sesi_id' => $this->sesi->id,
                'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
                'guru_id' => Guru::factory()->create()->id,
                'ruang_id' => Ruang::factory()->create()->id,
                'siswa_id' => Siswa::factory()->create()->id,
                'kode_kelas' => (string) Str::uuid(),
            ]);
        }
    }

    private function unggah(string $isi)
    {
        return $this->actingAs(User::factory()->create())
            ->post(route('admin.jadwal.uploadStash'), [
                'file_stash' => UploadedFile::fake()->createWithContent('x.stash', $isi),
            ]);
    }

    private function stash(): StashJadwalService
    {
        return app(StashJadwalService::class);
    }

    public function test_pemulihan_mencatat_siapa_dan_menyimpan_kondisi_sebelumnya(): void
    {
        $this->buatJadwal(3);
        $sebelum = $this->stash()->encode($this->stash()->bungkus());

        Jadwal::query()->delete();
        $this->buatJadwal(1);

        $this->unggah($sebelum)->assertOk();

        $log = StashPemulihanLog::latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(1, $log->jumlah_sebelum, 'Kondisi sebelum pemulihan harus tercatat.');
        $this->assertSame(3, $log->jumlah_sesudah);
        $this->assertNotNull($log->dipulihkan_oleh, 'Harus tahu siapa yang memulihkan.');
        $this->assertSame(3, Jadwal::count());
    }

    public function test_kondisi_sebelumnya_bisa_diunduh_dan_dipulihkan_kembali(): void
    {
        $this->buatJadwal(2);
        $kodeAsli = Jadwal::orderBy('id')->pluck('kode_kelas')->all();
        $stashBaru = $this->stash()->encode($this->stash()->bungkus(collect()));

        $this->unggah($stashBaru)->assertOk();
        $this->assertSame(0, Jadwal::count(), 'Stash kosong mengosongkan jadwal.');

        $log = StashPemulihanLog::latest('id')->first();
        $unduhan = $this->actingAs(User::factory()->create())
            ->get(route('admin.jadwal.unduhCadanganStash', $log->id));
        $unduhan->assertOk();

        $this->unggah($unduhan->getContent())->assertOk();

        $this->assertSame(2, Jadwal::count(), 'Jadwal lama harus bisa dikembalikan utuh.');
        $this->assertSame($kodeAsli, Jadwal::orderBy('id')->pluck('kode_kelas')->all());
    }

    public function test_stash_yang_menunjuk_data_terhapus_ditolak_dengan_pesan_jelas(): void
    {
        $this->buatJadwal(2);
        $sebelum = Jadwal::count();

        $isi = $this->stash()->encode([
            'app' => StashJadwalService::PENANDA_APLIKASI,
            'content' => [[
                'h' => $this->hari->id, 's' => $this->sesi->id, 'm' => 999901,
                'g' => 999902, 'r' => 999903, 'si' => 999904, 'k' => null,
            ]],
        ]);

        $respon = $this->unggah($isi);

        $respon->assertStatus(422);
        $this->assertStringContainsString('sudah tidak ada', $respon->json('message'));
        $this->assertSame($sebelum, Jadwal::count(), 'Jadwal lama tidak boleh tersentuh.');
    }

    public function test_pemulihan_memperingatkan_bentrok_yang_ikut_masuk(): void
    {
        $sesiBertindih = Sesi::factory()->create(['name' => 'Sesi Tindih', 'start_time' => '08:30', 'end_time' => '09:30']);
        $guru = Guru::factory()->create();
        $ruang = Ruang::factory()->create();
        $mapel = MataPelajaran::factory()->create();

        $baris = [
            ['h' => $this->hari->id, 's' => $this->sesi->id, 'm' => $mapel->id, 'g' => $guru->id, 'r' => $ruang->id, 'si' => Siswa::factory()->create()->id, 'k' => null],
            ['h' => $this->hari->id, 's' => $sesiBertindih->id, 'm' => $mapel->id, 'g' => $guru->id, 'r' => $ruang->id, 'si' => Siswa::factory()->create()->id, 'k' => null],
        ];

        $respon = $this->unggah($this->stash()->encode([
            'app' => StashJadwalService::PENANDA_APLIKASI,
            'content' => $baris,
        ]));

        $respon->assertOk();
        $this->assertStringContainsString('bentrok ikut masuk', $respon->json('message'));
        $this->assertGreaterThan(0, StashPemulihanLog::latest('id')->first()->bentrok_masuk);
    }

    public function test_file_bukan_stash_ditolak(): void
    {
        $this->buatJadwal(2);

        $respon = $this->unggah(base64_encode(json_encode(['app' => 'Aplikasi Lain', 'content' => []])));

        $respon->assertStatus(422);
        $this->assertSame(2, Jadwal::count());
    }
}
