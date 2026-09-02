<?php

namespace Tests\Feature;

use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RapikanDuplikatPembayaranTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Duplikat yang dirapikan command ini lahir SEBELUM kunci UNIQUE terpasang.
     * Agar keadaan itu bisa ditirukan, kuncinya dilepas dulu di sini -- persis
     * kondisi database produksi yang datanya menunggu dibereskan.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('pembayarans', function ($table) {
            $table->dropUnique('pembayarans_anchor_unique');
        });
    }

    /**
     * Skenario nyata dari produksi: tagihan manual dibuat lebih dulu dan
     * dibayar, lalu penagihan massal membuat tagihan kembar, dan setoran yang
     * sama tercatat lagi di tagihan kembar itu akibat klik ganda.
     */
    private function skenarioKlikGanda(): array
    {
        $paket = Paket::create(['nama_paket' => 'SD All Mapel 2X/Minggu', 'harga' => 275000, 'pertemuan' => 2]);
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121282', 'paket_pembayaran' => $paket->id]);
        $periode = now()->format('Y-m');

        $induk = Pembayaran::create([
            'id_siswa' => $siswa->id, 'id_paket' => $paket->id, 'periode' => $periode,
            'no_hp' => $siswa->no_hp, 'harga' => 275000, 'status' => 2, 'total_sudah_dibayar' => 275000,
            'keterangan' => 'Pembayaran Paket SD All Mapel 2X/Minggu (2 Pertemuan)',
        ]);
        PembayaranDetail::create([
            'id_pembayaran' => $induk->id, 'pembayaran' => 275000, 'keterangan' => 'Pembayaran LES',
        ]);

        $ganda = Pembayaran::create([
            'id_siswa' => $siswa->id, 'id_paket' => $paket->id, 'periode' => $periode,
            'no_hp' => $siswa->no_hp, 'harga' => 275000, 'status' => 2, 'total_sudah_dibayar' => 275000,
            'keterangan' => 'Tagihan Paket SD All Mapel 2X/Minggu - '.now()->translatedFormat('F Y'),
        ]);
        PembayaranDetail::create([
            'id_pembayaran' => $ganda->id, 'pembayaran' => 275000, 'keterangan' => 'Pembayaran LES',
        ]);

        return [$induk, $ganda];
    }

    public function test_dry_run_changes_nothing(): void
    {
        [$induk, $ganda] = $this->skenarioKlikGanda();

        $this->artisan('pembayaran:rapikan-duplikat')->assertSuccessful();

        $this->assertNotNull($induk->fresh());
        $this->assertNotNull($ganda->fresh());
        $this->assertSame(2, PembayaranDetail::count());
        $this->assertSame(0, DB::table('koreksi_pembayaran_logs')->count());
    }

    public function test_keeps_original_invoice_and_drops_the_phantom_copy(): void
    {
        [$induk, $ganda] = $this->skenarioKlikGanda();

        $this->artisan('pembayaran:rapikan-duplikat --force')->assertSuccessful();

        $this->assertNotNull($induk->fresh(), 'Tagihan induk harus dipertahankan.');
        $this->assertNull($ganda->fresh(), 'Tagihan kembar harus dibuang.');

        // Uang yang benar-benar masuk tetap satu kali, tidak berlipat.
        $this->assertSame(1, PembayaranDetail::count());
        $this->assertSame(275000, (int) $induk->fresh()->total_sudah_dibayar);
        $this->assertSame(2, (int) $induk->fresh()->status);
    }

    public function test_archives_everything_it_removes(): void
    {
        [$induk, $ganda] = $this->skenarioKlikGanda();

        $this->artisan('pembayaran:rapikan-duplikat --force')->assertSuccessful();

        $log = DB::table('koreksi_pembayaran_logs')->first();
        $this->assertNotNull($log, 'Baris yang dibuang wajib terarsip.');
        $this->assertSame($induk->id, (int) $log->id_pembayaran_induk);
        $this->assertSame($ganda->id, (int) $log->id_pembayaran_dibuang);

        $arsip = json_decode($log->data_asli, true);
        $this->assertSame($ganda->id, $arsip['pembayaran']['id']);
        $this->assertCount(1, $arsip['pembayaran_details']);
    }

    public function test_moves_genuinely_different_payment_instead_of_dropping_it(): void
    {
        // Setoran yang BEDA (bukan kembar) tidak boleh hilang -- harus pindah
        // ke tagihan induk, karena itu uang nyata.
        [$induk, $ganda] = $this->skenarioKlikGanda();
        PembayaranDetail::create([
            'id_pembayaran' => $ganda->id, 'pembayaran' => 125000, 'keterangan' => 'Angsuran tambahan',
        ]);

        $this->artisan('pembayaran:rapikan-duplikat --force')->assertSuccessful();

        $tersisa = PembayaranDetail::where('id_pembayaran', $induk->id)->get();
        $this->assertCount(2, $tersisa);
        $this->assertTrue($tersisa->contains(fn ($d) => (int) $d->pembayaran === 125000));
        $this->assertSame(400000, (int) $induk->fresh()->total_sudah_dibayar);
    }

    public function test_leaves_a_genuine_shortfall_as_outstanding(): void
    {
        // Kalau setelah dirapikan uangnya memang kurang, tagihan harus jujur
        // menunjukkan tunggakan -- bukan ditutup seolah lunas.
        $paket = Paket::create(['nama_paket' => 'TKA', 'harga' => 350000, 'pertemuan' => 3]);
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121290', 'paket_pembayaran' => $paket->id]);
        $periode = now()->format('Y-m');

        $induk = Pembayaran::create([
            'id_siswa' => $siswa->id, 'id_paket' => $paket->id, 'periode' => $periode,
            'no_hp' => $siswa->no_hp, 'harga' => 350000, 'status' => 0, 'total_sudah_dibayar' => 0,
            'keterangan' => 'Tagihan manual',
        ]);
        $ganda = Pembayaran::create([
            'id_siswa' => $siswa->id, 'id_paket' => $paket->id, 'periode' => $periode,
            'no_hp' => $siswa->no_hp, 'harga' => 350000, 'status' => 2, 'total_sudah_dibayar' => 350000,
            'keterangan' => 'Tagihan massal',
        ]);
        PembayaranDetail::create([
            'id_pembayaran' => $ganda->id, 'pembayaran' => 350000, 'keterangan' => 'Selesai sistem',
        ]);

        $this->artisan('pembayaran:rapikan-duplikat --force')->assertSuccessful();

        $hasil = $induk->fresh();
        $this->assertNotNull($hasil);
        $this->assertSame(0, (int) $hasil->total_sudah_dibayar);
        $this->assertSame(0, (int) $hasil->status, 'Tunggakan nyata harus tetap tampak sebagai belum bayar.');
    }

    public function test_is_idempotent_and_clears_the_audit(): void
    {
        $this->skenarioKlikGanda();

        $this->artisan('pembayaran:rapikan-duplikat --force')->assertSuccessful();
        $this->artisan('pembayaran:rapikan-duplikat --force')->assertSuccessful();
        $this->artisan('pembayaran:audit-duplikat')->assertSuccessful();

        $this->assertSame(1, DB::table('koreksi_pembayaran_logs')->count());
    }
}
