<?php

namespace Tests\Feature;

use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Services\PaymentBatchService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelumDitagihBulanIniTest extends TestCase
{
    use RefreshDatabase;

    private PaymentBatchService $batch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->batch = app(PaymentBatchService::class);
    }

    private function paket(string $nama, int $harga = 350_000): Paket
    {
        return Paket::create(['nama_paket' => $nama, 'harga' => $harga, 'pertemuan' => 3]);
    }

    /**
     * @param  array<int, Paket>  $pakets
     */
    private function siswa(string $nama, array $pakets): Siswa
    {
        $kolom = ['paket_pembayaran', 'paket_pembayaran_2', 'paket_pembayaran_3', 'paket_pembayaran_4', 'paket_pembayaran_5'];
        $isi = ['name' => $nama, 'no_hp' => '+6285290202208'];

        foreach ($pakets as $i => $paket) {
            $isi[$kolom[$i]] = $paket->id;
        }

        return Siswa::create($isi);
    }

    private function tagihanPaket(Siswa $siswa, Paket $paket, ?Carbon $saat = null): Pembayaran
    {
        $saat ??= Carbon::now();

        return tap(Pembayaran::create([
            'id_siswa' => $siswa->id,
            'id_paket' => $paket->id,
            'periode' => $saat->format('Y-m'),
            'no_hp' => $siswa->no_hp,
            'harga' => $paket->harga,
            'keterangan' => "Tagihan Paket {$paket->nama_paket} - ".$saat->translatedFormat('F Y'),
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]), fn (Pembayaran $p) => $p->forceFill(['created_at' => $saat, 'updated_at' => $saat])->save());
    }

    private function tagihanBebas(Siswa $siswa, string $keterangan, int $harga = 350_000): Pembayaran
    {
        return Pembayaran::create([
            'id_siswa' => $siswa->id,
            'id_paket' => null,
            'periode' => null,
            'no_hp' => $siswa->no_hp,
            'harga' => $harga,
            'keterangan' => $keterangan,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function paketYangDilaporkan(string $namaSiswa): array
    {
        return $this->batch->previewMissingInvoices()
            ->where('siswa_name', $namaSiswa)
            ->pluck('paket')
            ->values()
            ->all();
    }

    public function test_paket_yang_belum_punya_tagihan_apa_pun_tampil(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_ditagih_dengan_paket_yang_benar_membuat_barisnya_hilang(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanPaket($siswa, $paket);

        $this->assertSame([], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_ditagih_dengan_paket_yang_salah_tetap_tampil(): void
    {
        $terdaftar = $this->paket('mapel smp 2x');
        $paketKeliru = $this->paket('SMP Mapel Ing. 3X Meetings');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$terdaftar]);

        $this->tagihanPaket($siswa, $paketKeliru);

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_ditagih_tanpa_memilih_paket_tetap_tampil(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanBebas($siswa, 'Tagihan Paket SMP Mapel Ing. 3X Meetings - September 2026');

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_dari_dua_paket_hanya_yang_belum_ditagih_yang_tampil(): void
    {
        $satu = $this->paket('SMP Mapel Ing. 3X Meetings');
        $dua = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$satu, $dua]);

        $this->tagihanPaket($siswa, $satu);

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_dua_paket_yang_dua_duanya_ditagih_benar_hilang_semua(): void
    {
        $satu = $this->paket('SMP Mapel Ing. 3X Meetings');
        $dua = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$satu, $dua]);

        $this->tagihanPaket($siswa, $satu);
        $this->tagihanPaket($siswa, $dua);

        $this->assertSame([], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_tagihan_paket_benar_tapi_bulan_lalu_tidak_menutup_bulan_ini(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanPaket($siswa, $paket, Carbon::now()->subMonthNoOverflow()->startOfMonth()->addDay());

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_tagihan_milik_siswa_lain_tidak_menutup_paket_siapa_pun(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $this->siswa('AHZAGHANI ADIPRANA', [$paket]);
        $narendra = $this->siswa('NARENDRA', [$paket]);

        $this->tagihanPaket($narendra, $paket);

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
        $this->assertSame([], $this->paketYangDilaporkan('NARENDRA'));
    }

    public function test_salah_tagih_tidak_menghalangi_penagihan_massal_menagih_paket_yang_benar(): void
    {
        $terdaftar = $this->paket('mapel smp 2x');
        $paketKeliru = $this->paket('SMP Mapel Ing. 3X Meetings');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$terdaftar]);

        $this->tagihanPaket($siswa, $paketKeliru);

        $this->assertSame(1, $this->batch->createMonthlyInvoices());
        $this->assertDatabaseHas('pembayarans', [
            'id_siswa' => $siswa->id,
            'id_paket' => $terdaftar->id,
            'periode' => Carbon::now()->format('Y-m'),
        ]);
    }

    public function test_penagihan_massal_melewati_paket_yang_sudah_ditagih_benar(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanPaket($siswa, $paket);

        $this->assertSame(0, $this->batch->createMonthlyInvoices());
        $this->assertSame(1, Pembayaran::where('id_siswa', $siswa->id)->count());
    }

    public function test_panel_sejalan_dengan_apa_yang_akan_dibuat_penagihan_massal(): void
    {
        $satu = $this->paket('SMP Mapel Ing. 3X Meetings');
        $dua = $this->paket('mapel smp 2x');
        $paketKeliru = $this->paket('SD English Class 2X/Minggu');

        $adiprana = $this->siswa('AHZAGHANI ADIPRANA', [$satu, $dua]);
        $narendra = $this->siswa('NARENDRA', [$dua]);

        $this->tagihanPaket($adiprana, $satu);
        $this->tagihanPaket($narendra, $paketKeliru);
        $this->tagihanBebas($adiprana, 'Denda buku');

        $sebelum = $this->batch->previewMissingInvoices()->count();

        $this->assertSame($sebelum, $this->batch->createMonthlyInvoices());
        $this->assertSame(0, $this->batch->previewMissingInvoices()->count());
    }
}
