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

    private function tagihanBebas(Siswa $siswa, string $keterangan, int $harga = 350_000, ?Carbon $saat = null): Pembayaran
    {
        $saat ??= Carbon::now();

        return tap(Pembayaran::create([
            'id_siswa' => $siswa->id,
            'id_paket' => null,
            'periode' => null,
            'no_hp' => $siswa->no_hp,
            'harga' => $harga,
            'keterangan' => $keterangan,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]), fn (Pembayaran $p) => $p->forceFill(['created_at' => $saat, 'updated_at' => $saat])->save());
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

    public function test_siswa_satu_paket_yang_ditagih_manual_tanpa_memilih_paket_tidak_lagi_muncul(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));

        $this->tagihanBebas($siswa, 'Tagihan Paket SMP Mapel Ing. 3X Meetings - September 2026');

        $this->assertSame([], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_siswa_yang_ditagih_lewat_penagihan_massal_juga_tidak_muncul(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanPaket($siswa, $paket);

        $this->assertSame([], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_paket_kedua_yang_belum_ditagih_tetap_terlihat(): void
    {
        $satu = $this->paket('SMP Mapel Ing. 3X Meetings');
        $dua = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$satu, $dua]);

        $this->tagihanPaket($siswa, $satu);

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_satu_tagihan_bebas_hanya_menutup_satu_paket(): void
    {
        $satu = $this->paket('SMP Mapel Ing. 3X Meetings');
        $dua = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$satu, $dua]);

        $this->tagihanBebas($siswa, 'Tagihan manual September');

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_tagihan_anchor_dan_tagihan_bebas_bersama_menutup_dua_paket(): void
    {
        $satu = $this->paket('SMP Mapel Ing. 3X Meetings');
        $dua = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$satu, $dua]);

        $this->tagihanPaket($siswa, $satu);
        $this->tagihanBebas($siswa, 'Tagihan manual September');

        $this->assertSame([], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_tagihan_bebas_bulan_lalu_tidak_ikut_menutup_bulan_ini(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanBebas($siswa, 'Tagihan manual bulan lalu', 350_000, Carbon::now()->subMonthNoOverflow()->startOfMonth()->addDay());

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
    }

    public function test_tagihan_bebas_milik_siswa_lain_tidak_menutup_paket_siapa_pun(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $adiprana = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);
        $narendra = $this->siswa('NARENDRA', [$paket]);

        $this->tagihanBebas($narendra, 'Tagihan manual September');

        $this->assertSame(['mapel smp 2x'], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));
        $this->assertSame([], $this->paketYangDilaporkan('NARENDRA'));
        $this->assertNotNull($adiprana->id);
    }

    public function test_tagihan_bebas_tidak_mengubah_perilaku_penagihan_massal(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanBebas($siswa, 'Tagihan manual September');

        $this->assertSame([], $this->paketYangDilaporkan('AHZAGHANI ADIPRANA'));

        $dibuat = $this->batch->createMonthlyInvoices();

        $this->assertSame(1, $dibuat);
        $this->assertDatabaseHas('pembayarans', [
            'id_siswa' => $siswa->id,
            'id_paket' => $paket->id,
            'periode' => Carbon::now()->format('Y-m'),
        ]);
    }

    public function test_penagihan_massal_tetap_melewati_paket_yang_sudah_punya_anchor(): void
    {
        $paket = $this->paket('mapel smp 2x');
        $siswa = $this->siswa('AHZAGHANI ADIPRANA', [$paket]);

        $this->tagihanPaket($siswa, $paket);

        $this->assertSame(0, $this->batch->createMonthlyInvoices());
        $this->assertSame(1, Pembayaran::where('id_siswa', $siswa->id)->count());
    }
}
