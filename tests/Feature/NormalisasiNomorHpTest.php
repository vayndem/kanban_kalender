<?php

namespace Tests\Feature;

use App\Console\Commands\NormalisasiNomorHp;
use App\Models\Pembayaran;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NormalisasiNomorHpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bentuk-bentuk yang benar-benar ditemukan di database produksi.
     */
    public static function nomorProvider(): array
    {
        return [
            'diawali nol' => ['085234539034', '+6285234539034'],
            'diawali delapan tanpa nol' => ['85640744704', '+6285640744704'],
            'diawali 62 tanpa plus' => ['6285234539034', '+6285234539034'],
            'sudah benar' => ['+6285234539034', '+6285234539034'],
            'mengandung spasi' => ['0882 0077 56705', '+62882007756705'],
            'nomor pendek diawali nol' => ['0811297374', '+62811297374'],
        ];
    }

    #[DataProvider('nomorProvider')]
    public function test_normalizes_known_phone_formats(string $masukan, string $harapan): void
    {
        $this->assertSame($harapan, NormalisasiNomorHp::normalkan($masukan));
    }

    public function test_leaves_unrecognised_values_untouched(): void
    {
        // null / kosong / bentuk aneh tidak ditebak-tebak, dibiarkan apa adanya.
        $this->assertNull(NormalisasiNomorHp::normalkan(null));
        $this->assertNull(NormalisasiNomorHp::normalkan(''));
        $this->assertNull(NormalisasiNomorHp::normalkan('   '));
        $this->assertNull(NormalisasiNomorHp::normalkan('tidak ada'));
    }

    public function test_dry_run_changes_nothing(): void
    {
        $siswa = Siswa::factory()->create(['no_hp' => '085234539034']);

        $this->artisan('pembayaran:normalisasi-hp')->assertSuccessful();

        $this->assertSame('085234539034', $siswa->fresh()->no_hp);
    }

    public function test_force_updates_every_table_in_step(): void
    {
        // Kalau siswas berubah tapi pembayarans tidak, kartu keluarga pecah dan
        // tagihan kehilangan induknya -- ini yang dijaga tes ini.
        $siswa = Siswa::factory()->create(['no_hp' => '085234539034']);
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => '085234539034',
            'harga' => 350000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();

        $this->assertSame('+6285234539034', $siswa->fresh()->no_hp);
        $this->assertSame('+6285234539034', $tagihan->fresh()->no_hp);
    }

    public function test_merges_families_split_by_inconsistent_format(): void
    {
        $kakak = Siswa::factory()->create(['no_hp' => '+6281328377375']);
        $adik = Siswa::factory()->create(['no_hp' => '081328377375']);

        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();

        $this->assertSame($kakak->fresh()->no_hp, $adik->fresh()->no_hp);
        $this->assertSame(1, Siswa::whereIn('id', [$kakak->id, $adik->id])
            ->distinct()->count('no_hp'));
    }

    public function test_is_idempotent(): void
    {
        $siswa = Siswa::factory()->create(['no_hp' => '085234539034']);

        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();
        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();

        $this->assertSame('+6285234539034', $siswa->fresh()->no_hp);
    }
}
