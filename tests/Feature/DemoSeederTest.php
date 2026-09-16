<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\AspekPenilaian;
use App\Models\BatchPembayaranLog;
use App\Models\Diskon;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\JadwalTeksLog;
use App\Models\ModulAjarAbsensi;
use App\Models\ModulAjarDetail;
use App\Models\NilaiAspek;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Penggajian;
use App\Models\Pertemuan;
use App\Models\RaporCetak;
use App\Models\Siswa;
use App\Models\StashPemulihanLog;
use App\Models\Tanda;
use App\Services\IrisanSesiService;
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

    public function test_seeder_menyediakan_sesi_yang_waktunya_bertindih(): void
    {
        $peta = app(IrisanSesiService::class)->peta();
        $adaYangBertindih = collect($peta)->contains(fn (array $ids) => count($ids) > 1);

        $this->assertTrue($adaYangBertindih, 'Data demo harus memuat sesi bertindih agar aturannya ikut teruji.');
    }

    public function test_jadwal_demo_tidak_bentrok_di_sesi_yang_waktunya_bertindih(): void
    {
        $peta = app(IrisanSesiService::class)->peta();

        $terpakai = [];
        foreach (Jadwal::all() as $j) {
            $terpakai[$j->hari_id][$j->sesi_id]['ruang'][$j->ruang_id] = true;
            $terpakai[$j->hari_id][$j->sesi_id]['guru'][$j->guru_id] = true;
            $terpakai[$j->hari_id][$j->sesi_id]['siswa'][$j->siswa_id] = true;
        }

        $bentrok = [];
        foreach ($terpakai as $hariId => $perSesi) {
            foreach ($perSesi as $sesiA => $isiA) {
                foreach ($peta[$sesiA] ?? [] as $sesiB) {
                    if ($sesiB <= $sesiA || ! isset($perSesi[$sesiB])) {
                        continue;
                    }
                    foreach (['ruang', 'guru', 'siswa'] as $jenis) {
                        foreach (array_keys($isiA[$jenis] ?? []) as $id) {
                            if (isset($perSesi[$sesiB][$jenis][$id])) {
                                $bentrok[] = "{$jenis} #{$id} pada hari {$hariId} (sesi {$sesiA} vs {$sesiB})";
                            }
                        }
                    }
                }
            }
        }

        $this->assertSame([], $bentrok, 'Data demo tidak boleh memakai ruang/guru/siswa yang sama di sesi bertindih.');
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

            $this->assertCount(0, $bentrok, 'Ada siswa terjadwal ganda pada slot yang sama.');
        }
    }

    public function test_every_class_gets_a_stable_kode_kelas_for_modul_ajar(): void
    {
        $this->assertSame(0, DB::table('jadwals')->whereNull('kode_kelas')->count());

        $kodeKelasPerKombinasi = DB::table('jadwals')
            ->select(['hari_id', 'sesi_id', 'mata_pelajaran_id', 'guru_id', 'ruang_id', 'kode_kelas'])
            ->get()
            ->groupBy(fn ($r) => "{$r->hari_id}_{$r->sesi_id}_{$r->mata_pelajaran_id}_{$r->guru_id}_{$r->ruang_id}")
            ->map(fn ($rows) => $rows->pluck('kode_kelas')->unique());

        foreach ($kodeKelasPerKombinasi as $kombinasi => $kodeKelasList) {
            $this->assertCount(1, $kodeKelasList, "Kombinasi kelas {$kombinasi} punya lebih dari satu kode_kelas.");
        }

        $semuaKodeKelas = $kodeKelasPerKombinasi->flatten();
        $this->assertSame($semuaKodeKelas->count(), $semuaKodeKelas->unique()->count(), 'Dua kelas berbeda tidak boleh berbagi kode_kelas yang sama.');
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

    public function test_demo_memuat_keadaan_mengajar_yang_sedang_berlangsung(): void
    {
        $this->assertGreaterThan(0, Pertemuan::count(), 'Harus ada pertemuan bertanggal.');

        $this->assertGreaterThan(
            0,
            Pertemuan::whereNull('selesai_pada')->count(),
            'Harus ada pertemuan yang sedang berlangsung, kalau tidak kartu kuning di Absen tidak pernah terlihat.'
        );

        $this->assertGreaterThan(
            0,
            ModulAjarDetail::where('tidak_bisa_hadir', true)->count(),
            'Harus ada slot terbuka supaya alur guru pengganti bisa dicoba.'
        );

        $this->assertGreaterThan(
            0,
            Pertemuan::whereNotNull('guru_pengganti_id')->count(),
            'Harus ada pertemuan yang dipegang guru pengganti.'
        );
    }

    public function test_kredit_kehadiran_ada_yang_jatuh_ke_guru_pengganti(): void
    {
        $adaBeda = Pertemuan::whereNotNull('selesai_pada')
            ->with('modulAjarDetail.modulAjar')
            ->get()
            ->contains(function (Pertemuan $pertemuan) {
                $kode = $pertemuan->modulAjarDetail?->modulAjar?->kode_kelas;
                $pemilik = Jadwal::where('kode_kelas', $kode)->value('guru_id');
                $kredit = AbsensiGuru::where('pertemuan_id', $pertemuan->id)->value('guru_id');

                return $pemilik && $kredit && (int) $pemilik !== (int) $kredit;
            });

        $this->assertTrue($adaBeda, 'Harus ada satu kelas yang kredit kehadirannya jatuh ke guru pengganti.');
    }

    public function test_demo_memuat_aspek_aktif_dan_aspek_yang_dipensiunkan(): void
    {
        $this->assertGreaterThanOrEqual(5, AspekPenilaian::where('aktif', true)->count());
        $this->assertGreaterThan(
            0,
            AspekPenilaian::where('aktif', false)->count(),
            'Harus ada aspek nonaktif supaya lencana Nonaktif dan rapor lama ikut teruji.'
        );

        $pensiun = AspekPenilaian::where('aktif', false)->first();
        $this->assertGreaterThan(
            0,
            NilaiAspek::where('aspek_penilaian_id', $pensiun->id)->count(),
            'Aspek yang dipensiunkan harus tetap menyimpan nilai lama.'
        );
    }

    public function test_setiap_anak_yang_hadir_dinilai_di_semua_aspek_aktif(): void
    {
        $jumlahAktif = AspekPenilaian::where('aktif', true)->count();

        $kurang = ModulAjarAbsensi::where('hadir', true)
            ->withCount(['nilaiAspeks as aktif_count' => fn ($q) => $q->whereHas('aspek', fn ($a) => $a->where('aktif', true))])
            ->get()
            ->filter(fn ($a) => $a->aktif_count < $jumlahAktif);

        $this->assertCount(0, $kurang, 'Ada anak hadir yang penilaiannya tidak lengkap di data demo.');
    }

    public function test_demo_memuat_struk_gaji_yang_dibatalkan(): void
    {
        $this->assertGreaterThan(
            0,
            Penggajian::whereNotNull('dibatalkan_pada')->count(),
            'Alur batalkan-lalu-terbitkan-ulang harus terlihat di data demo.'
        );

        $dibatalkan = Penggajian::whereNotNull('dibatalkan_pada')->first();
        $this->assertNotNull($dibatalkan->alasan_batal, 'Pembatalan wajib punya alasan.');
        $this->assertSame(
            0,
            AbsensiGuru::where('penggajian_id', $dibatalkan->id)->count(),
            'Kehadiran pada struk yang dibatalkan harus dilepas kembali.'
        );
    }

    public function test_demo_memuat_guru_yang_belum_disiapkan_admin(): void
    {
        $this->assertGreaterThan(
            0,
            Guru::whereNull('email')->count(),
            'Harus ada guru tanpa akun, karena itu keadaan mayoritas di produksi.'
        );
    }

    public function test_demo_memuat_tagihan_di_luar_paket(): void
    {
        $bebas = Pembayaran::whereNull('id_paket')->get();

        $this->assertGreaterThan(0, $bebas->count(), 'Tagihan buku/denda harus ada supaya jalur non-paket teruji.');
        $this->assertTrue(
            $bebas->contains(fn ($p) => (int) $p->status === 2),
            'Setidaknya satu tagihan non-paket sudah lunas.'
        );
    }

    public function test_demo_memuat_siswa_yang_belum_terjadwal_dan_paket_bertumpuk(): void
    {
        $belumTerjadwal = Siswa::whereDoesntHave('jadwals')->count();
        $this->assertGreaterThan(0, $belumTerjadwal, 'Panel Kebersihan Data butuh siswa tanpa jadwal.');

        $bertumpuk = Siswa::whereNotNull('paket_pembayaran')
            ->whereNotNull('paket_pembayaran_3')
            ->count();
        $this->assertGreaterThan(0, $bertumpuk, 'Harus ada siswa dengan lebih dari dua paket.');
    }

    public function test_demo_memuat_catatan_yang_sudah_lewat_ambang_batas(): void
    {
        $this->assertGreaterThan(
            0,
            Tanda::where('created_at', '<', now()->subDays(14))->count(),
            'Peringatan tanda lama di Ringkasan butuh catatan berumur lebih dari 14 hari.'
        );
    }

    public function test_demo_memuat_jejak_operasional(): void
    {
        $this->assertGreaterThan(0, BatchPembayaranLog::count(), 'Kunci penagihan massal belum tercatat.');
        $this->assertGreaterThan(0, JadwalTeksLog::count(), 'Log salin teks jadwal belum ada.');
        $this->assertGreaterThan(0, StashPemulihanLog::count(), 'Catatan pemulihan stash belum ada.');

        $stash = StashPemulihanLog::first();
        $this->assertNotEmpty($stash->isi_sebelum, 'Cadangan stash harus berisi kondisi sebelumnya.');
    }

    public function test_demo_memuat_materi_yang_diajar_lebih_dari_sekali(): void
    {
        $berulang = Pertemuan::whereNotNull('selesai_pada')
            ->get()
            ->groupBy('modul_ajar_detail_id')
            ->filter(fn ($p) => $p->count() > 1);

        $this->assertGreaterThan(
            0,
            $berulang->count(),
            'Harus ada materi yang diajar dua kali, supaya riwayat per pertemuan ikut teruji.'
        );

        $detailId = $berulang->keys()->first();
        $tanggal = Pertemuan::where('modul_ajar_detail_id', $detailId)->pluck('tanggal');

        $this->assertSame(
            $tanggal->count(),
            $tanggal->map(fn ($t) => $t->toDateString())->unique()->count(),
            'Tiap pertemuan pada materi yang sama harus punya tanggal berbeda.'
        );
    }

    public function test_setiap_pertemuan_selesai_punya_kredit_kehadiran_sendiri(): void
    {
        $selesai = Pertemuan::whereNotNull('selesai_pada')->pluck('id');

        $this->assertSame(
            $selesai->count(),
            AbsensiGuru::whereIn('pertemuan_id', $selesai)->count(),
            'Satu pertemuan selesai berarti satu kredit kehadiran mengajar.'
        );
    }

    public function test_demo_memuat_riwayat_cetak_rapor_dengan_catatan(): void
    {
        $this->assertGreaterThan(0, RaporCetak::count(), 'Halaman rapor orang tua butuh catatan dari cetakan.');

        $cetak = RaporCetak::first();

        $this->assertTrue($cetak->adaCatatan());
        $this->assertNotEmpty($cetak->pertemuan_ids);
        $this->assertNotNull($cetak->periode_label);
    }

    public function test_ada_siswa_yang_raporya_bisa_dibuka_orang_tua(): void
    {
        $cetak = RaporCetak::with('siswa')->firstOrFail();
        $siswa = $cetak->siswa;

        $this->assertNotNull($siswa->no_hp, 'Orang tua butuh nomor HP terdaftar untuk membuka rapor.');

        $empatDigit = substr(preg_replace('/\D/', '', $siswa->no_hp), -4);

        $this->post(route('rapor.publik.cari'), ['nama' => $siswa->name, 'empat_digit' => $empatDigit])
            ->assertOk()
            ->assertSee($siswa->name, false);
    }
}
