<?php

namespace Tests\Feature;

use App\Models\Diskon;
use App\Models\Jadwal;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Siswa;
use Carbon\Carbon;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Data demo dipakai untuk mencoba fitur pembayaran dan jadwal. Kalau datanya
 * sendiri tidak konsisten, hasil percobaan jadi menyesatkan -- karena itu
 * aturan-aturan penting sistem diuji langsung pada data yang dihasilkannya.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    public function test_it_produces_a_populated_school(): void
    {
        $this->assertGreaterThanOrEqual(15, Siswa::count());
        $this->assertGreaterThan(0, Jadwal::count());
        $this->assertGreaterThan(0, Pembayaran::count());
        $this->assertGreaterThan(0, Diskon::count());
    }

    public function test_every_phone_number_uses_the_plus_62_format(): void
    {
        $menyimpang = Siswa::whereNotNull('no_hp')
            ->get(['name', 'no_hp'])
            ->reject(fn ($s) => str_starts_with($s->no_hp, '+62'));

        $this->assertCount(0, $menyimpang, 'Ada nomor HP demo yang tidak berformat +62: '.$menyimpang->pluck('no_hp')->implode(', '));
    }

    public function test_it_never_creates_duplicate_package_invoices(): void
    {
        // Aturan inti anti-tagihan-ganda: satu siswa, satu paket, satu periode.
        $ganda = DB::table('pembayarans')
            ->select('id_siswa', 'id_paket', 'periode', DB::raw('COUNT(*) as jml'))
            ->whereNotNull('id_paket')
            ->whereNotNull('periode')
            ->groupBy('id_siswa', 'id_paket', 'periode')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->assertCount(0, $ganda, 'Data demo membuat tagihan ganda.');
    }

    public function test_payment_details_always_match_the_invoice_total(): void
    {
        // Buku besar (detail) harus cocok dengan header tagihan; kalau tidak,
        // angka di layar akan berbeda dengan riwayat pembayarannya.
        foreach (Pembayaran::with('details')->get() as $tagihan) {
            $this->assertSame(
                (int) $tagihan->total_sudah_dibayar,
                (int) $tagihan->details->sum('pembayaran'),
                "Tagihan #{$tagihan->id} tidak cocok dengan rincian setorannya."
            );
        }
    }

    public function test_invoice_status_is_consistent_with_the_amount_paid(): void
    {
        foreach (Pembayaran::all() as $tagihan) {
            $dibayar = (int) $tagihan->total_sudah_dibayar;
            $harga = (int) $tagihan->harga;

            $harapan = match (true) {
                $dibayar >= $harga => 2,
                $dibayar > 0 => 1,
                default => 0,
            };

            $this->assertSame($harapan, (int) $tagihan->status, "Status tagihan #{$tagihan->id} tidak sesuai nominalnya.");
        }
    }

    public function test_schedule_has_no_teacher_room_or_student_collision(): void
    {
        foreach (['guru_id', 'ruang_id', 'siswa_id'] as $kolom) {
            $bentrok = DB::table('jadwals')
                ->select('hari_id', 'sesi_id', $kolom, DB::raw('COUNT(*) as jml'))
                ->groupBy('hari_id', 'sesi_id', $kolom)
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($kolom === 'guru_id' || $kolom === 'ruang_id') {
                // Guru & ruang boleh menangani beberapa siswa dalam satu kelas,
                // tapi tidak boleh dua kelas berbeda pada slot yang sama.
                foreach ($bentrok as $baris) {
                    $kelasBerbeda = DB::table('jadwals')
                        ->where('hari_id', $baris->hari_id)
                        ->where('sesi_id', $baris->sesi_id)
                        ->where($kolom, $baris->{$kolom})
                        ->distinct()
                        ->count('mata_pelajaran_id');

                    $this->assertSame(1, $kelasBerbeda, "Bentrok {$kolom} pada hari {$baris->hari_id} sesi {$baris->sesi_id}.");
                }

                continue;
            }

            $this->assertCount(0, $bentrok, "Ada siswa terjadwal ganda pada slot yang sama.");
        }
    }

    public function test_running_it_twice_does_not_duplicate_anything(): void
    {
        $siswa = Siswa::count();
        $tagihan = Pembayaran::count();
        $jadwal = Jadwal::count();

        $this->seed(DemoSeeder::class);

        $this->assertSame($siswa, Siswa::count());
        $this->assertSame($tagihan, Pembayaran::count());
        $this->assertSame($jadwal, Jadwal::count());
    }

    public function test_it_covers_every_payment_status_for_realistic_testing(): void
    {
        foreach ([0, 1, 2] as $status) {
            $this->assertGreaterThan(
                0,
                Pembayaran::where('status', $status)->count(),
                "Data demo tidak punya contoh tagihan berstatus {$status}."
            );
        }
    }

    /**
     * created_at/updated_at bukan bagian $fillable Pembayaran/PembayaranDetail
     * (sengaja, di seluruh app), jadi seeder harus membekukannya lewat
     * forceFill setelah baris dibuat. Tanpa itu, riwayat 3 bulan yang jadi
     * tujuan seeder ini semuanya jatuh ke tanggal seed dijalankan.
     */
    public function test_invoice_history_actually_spans_three_months_in_the_past(): void
    {
        $duaBulanLalu = Carbon::now()->subMonths(2)->startOfMonth();
        $bulanIni = Carbon::now()->startOfMonth();

        $tagihanLama = Pembayaran::whereBetween('created_at', [
            $duaBulanLalu, $duaBulanLalu->copy()->endOfMonth(),
        ])->count();

        $this->assertGreaterThan(
            0,
            $tagihanLama,
            'Tidak ada tagihan yang benar-benar bertanggal 2 bulan lalu -- timestamp seeder kemungkinan dibuang saat create().'
        );

        $tagihanSemuaBulanIni = Pembayaran::where('created_at', '>=', $bulanIni)->count();
        $this->assertLessThan(
            Pembayaran::count(),
            $tagihanSemuaBulanIni,
            'Semua tagihan bertanggal bulan ini -- riwayat 3 bulan tidak benar-benar tercatat mundur.'
        );
    }

    public function test_payment_detail_dates_also_span_the_past_not_just_the_seed_run(): void
    {
        $duaBulanLalu = Carbon::now()->subMonths(2)->startOfMonth();

        $detailLama = PembayaranDetail::whereBetween('created_at', [
            $duaBulanLalu, $duaBulanLalu->copy()->endOfMonth(),
        ])->count();

        $this->assertGreaterThan(0, $detailLama, 'Tidak ada detail setoran yang bertanggal 2 bulan lalu.');
    }
}
