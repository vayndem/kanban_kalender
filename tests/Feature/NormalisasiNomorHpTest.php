<?php

namespace Tests\Feature;

use App\Console\Commands\NormalisasiNomorHp;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Sejak Siswa/Pembayaran/Arsip/Diskon punya mutator no_hp (MenormalisasiNoHp),
 * data yang lewat Eloquent otomatis rapi -- makanya tes di sini sengaja
 * menulis langsung ke tabel lewat DB::table() untuk mensimulasikan data lama
 * yang sudah ada di database sebelum mutator ini dipasang. Command ini masih
 * berguna untuk data seperti itu, atau data yang masuk lewat jalur di luar
 * Eloquent (mis. impor massal yang menulis mentah ke database).
 */
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

    public function test_new_records_are_normalized_automatically_by_the_model(): void
    {
        // Ini yang berubah sejak mutator dipasang: lewat Eloquent, data kotor
        // tidak pernah sempat tersimpan sama sekali.
        $siswa = Siswa::factory()->create(['no_hp' => '085234539034']);

        $this->assertSame('+6285234539034', $siswa->fresh()->no_hp);
    }

    public function test_dry_run_changes_nothing(): void
    {
        $siswa = Siswa::factory()->create();
        DB::table('siswas')->where('id', $siswa->id)->update(['no_hp' => '085234539034']);

        $this->artisan('pembayaran:normalisasi-hp')->assertSuccessful();

        $this->assertSame('085234539034', $siswa->fresh()->no_hp);
    }

    public function test_force_updates_every_table_in_step(): void
    {
        // Kalau siswas berubah tapi pembayarans tidak, kartu keluarga pecah dan
        // tagihan kehilangan induknya -- ini yang dijaga tes ini.
        $siswa = Siswa::factory()->create();
        DB::table('siswas')->where('id', $siswa->id)->update(['no_hp' => '085234539034']);

        $tagihanId = DB::table('pembayarans')->insertGetId([
            'id_siswa' => $siswa->id,
            'no_hp' => '085234539034',
            'harga' => 350000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();

        $this->assertSame('+6285234539034', $siswa->fresh()->no_hp);
        $this->assertSame('+6285234539034', DB::table('pembayarans')->where('id', $tagihanId)->value('no_hp'));
    }

    public function test_merges_families_split_by_inconsistent_format(): void
    {
        $kakak = Siswa::factory()->create();
        $adik = Siswa::factory()->create();
        DB::table('siswas')->where('id', $kakak->id)->update(['no_hp' => '+6281328377375']);
        DB::table('siswas')->where('id', $adik->id)->update(['no_hp' => '081328377375']);

        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();

        $this->assertSame($kakak->fresh()->no_hp, $adik->fresh()->no_hp);
        $this->assertSame(1, Siswa::whereIn('id', [$kakak->id, $adik->id])
            ->distinct()->count('no_hp'));
    }

    public function test_is_idempotent(): void
    {
        $siswa = Siswa::factory()->create();
        DB::table('siswas')->where('id', $siswa->id)->update(['no_hp' => '085234539034']);

        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();
        $this->artisan('pembayaran:normalisasi-hp --force')->assertSuccessful();

        $this->assertSame('+6285234539034', $siswa->fresh()->no_hp);
    }
}
