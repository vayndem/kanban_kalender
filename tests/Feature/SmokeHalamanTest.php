<?php

namespace Tests\Feature;

use App\Models\Penggajian;
use App\Models\Siswa;
use App\Models\StashPemulihanLog;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SmokeHalamanTest extends TestCase
{
    use RefreshDatabase;

    private const LEWATI = [
        'reset-password/{token}',
        'verify-email/{id}/{hash}',
        'storage/{path}',
        'up',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_setiap_halaman_get_tanpa_parameter_tidak_meledak(): void
    {
        $admin = User::factory()->create();
        $gagal = [];

        foreach ($this->rute(false) as $uri) {
            $response = $this->actingAs($admin)->get('/'.ltrim($uri, '/'));

            if ($response->getStatusCode() >= 500) {
                $gagal[] = $uri.' => '.($response->exception?->getMessage() ?? 'status '.$response->getStatusCode());
            }
        }

        $this->assertSame([], $gagal, "Halaman GET yang error:\n".implode("\n", $gagal));
    }

    public function test_setiap_halaman_get_berparameter_tidak_meledak_dengan_data_nyata(): void
    {
        $this->seed(DemoSeeder::class);

        $admin = User::factory()->create();
        $nilai = $this->nilaiParameter();
        $gagal = [];
        $diuji = 0;

        foreach ($this->rute(true) as $uri) {
            $jalur = $uri;
            $lengkap = true;

            foreach ($this->parameterDari($uri) as $param) {
                if (! isset($nilai[$param])) {
                    $lengkap = false;

                    break;
                }

                $jalur = str_replace(['{'.$param.'}', '{'.$param.'?}'], (string) $nilai[$param], $jalur);
            }

            if (! $lengkap) {
                $gagal[] = $uri.' => parameter tidak punya nilai uji';

                continue;
            }

            $diuji++;
            $response = $this->actingAs($admin)->get('/'.ltrim($jalur, '/'));

            if ($response->getStatusCode() >= 500) {
                $gagal[] = $jalur.' => '.($response->exception?->getMessage() ?? 'status '.$response->getStatusCode());
            }
        }

        $this->assertGreaterThan(0, $diuji);
        $this->assertSame([], $gagal, "Halaman GET berparameter yang error:\n".implode("\n", $gagal));
    }

    public function test_tombol_export_pdf_di_tab_jadwal_terikat_ke_url_yang_ada(): void
    {
        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'jadwal']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('pdfExportUrl', $halaman);
        $this->assertStringContainsString(':href="routes.jadwal.exportPdf"', $halaman);
        $this->assertStringContainsString(route('jadwal.kalender.export'), $halaman);
    }

    /**
     * @return list<string>
     */
    private function rute(bool $berparameter): array
    {
        $daftar = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (str_starts_with($uri, '_') || in_array($uri, self::LEWATI, true)) {
                continue;
            }

            if (str_contains($uri, '{') !== $berparameter) {
                continue;
            }

            $daftar[] = $uri;
        }

        return $daftar;
    }

    /**
     * @return list<string>
     */
    private function parameterDari(string $uri): array
    {
        preg_match_all('/\{(\w+)\??\}/', $uri, $cocok);

        return $cocok[1];
    }

    /**
     * @return array<string, int|string>
     */
    private function nilaiParameter(): array
    {
        $siswa = Siswa::whereNotNull('no_hp')->where('no_hp', '!=', '')->firstOrFail();

        return [
            'siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'penggajian' => Penggajian::firstOrFail()->id,
            'pemulihan' => StashPemulihanLog::firstOrFail()->id,
        ];
    }
}
