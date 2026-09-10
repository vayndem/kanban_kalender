<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\Tanda;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class KeamananEksporDanStashTest extends TestCase
{
    use RefreshDatabase;

    private const ISI_CATATAN = 'Orang tua menunggak tiga bulan dan sulit dihubungi';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $siswa = Siswa::factory()->create(['name' => 'Budi Rahasia', 'kelas' => '7A']);

        Jadwal::create([
            'hari_id' => Hari::create(['name' => 'Senin'])->id,
            'sesi_id' => Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00'])->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => Guru::factory()->create()->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'siswa_id' => $siswa->id,
        ]);

        Tanda::create(['siswa_id' => $siswa->id, 'keterangan' => self::ISI_CATATAN]);
    }

    private function isiPdf(string $mentah): string
    {
        return (new Parser)->parseContent($mentah)->getText();
    }

    public function test_pdf_publik_tidak_membocorkan_catatan_internal_siswa(): void
    {
        $respon = $this->get(route('jadwal.kalender.export'));
        $respon->assertOk();

        $teks = $this->isiPdf($respon->getContent());

        $this->assertStringNotContainsString(
            self::ISI_CATATAN,
            $teks,
            'Catatan internal tentang siswa tidak boleh ikut di PDF yang bisa diunduh tanpa login.'
        );
    }

    public function test_stash_rusak_ditolak_tanpa_menghapus_jadwal(): void
    {
        $sebelum = Jadwal::count();
        $this->assertGreaterThan(0, $sebelum);

        $rusak = base64_encode(json_encode([
            'app' => 'E-Ling-Course',
            'content' => [['h' => 1, 's' => 1]],
        ]));

        $this->actingAs(User::factory()->create())
            ->post(route('admin.jadwal.uploadStash'), [
                'file_stash' => UploadedFile::fake()->createWithContent('stash.txt', $rusak),
            ])
            ->assertStatus(422);

        $this->assertSame($sebelum, Jadwal::count(), 'Jadwal tidak boleh terhapus saat file stash ditolak.');
    }

    public function test_stash_tanpa_isi_ditolak(): void
    {
        $sebelum = Jadwal::count();

        $kosong = base64_encode(json_encode(['app' => 'E-Ling-Course']));

        $this->actingAs(User::factory()->create())
            ->post(route('admin.jadwal.uploadStash'), [
                'file_stash' => UploadedFile::fake()->createWithContent('stash.txt', $kosong),
            ])
            ->assertStatus(422);

        $this->assertSame($sebelum, Jadwal::count());
    }

    public function test_admin_tetap_mendapat_catatan_di_pdf(): void
    {
        $respon = $this->actingAs(User::factory()->create())->get(route('jadwal.kalender.export'));
        $respon->assertOk();

        $teks = $this->isiPdf($respon->getContent());

        $this->assertStringContainsString(self::ISI_CATATAN, $teks, 'Admin masih butuh catatan ini.');
    }
}
