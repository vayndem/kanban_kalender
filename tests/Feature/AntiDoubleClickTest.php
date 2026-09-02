<?php

namespace Tests\Feature;

use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Server lama di region Amerika lambat merespons, sehingga admin kerap menekan
 * tombol dua kali dan satu setoran tercatat ganda. Tes ini mengunci perilaku
 * penjaga di sisi server -- lapisan yang tetap bekerja walau overlay di layar
 * dilewati (refresh, tombol back, atau permintaan yang diulang jaringan).
 */
class AntiDoubleClickTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_identical_payment_submitted_twice(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121282']);
        Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'harga' => 550000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        $payload = [
            'nominal' => 275000,
            'keterangan_detail' => 'Pembayaran LES',
            'pembayaran_via' => 0,
            'tanggal_pembayaran' => now()->toDateString(),
        ];

        $this->actingAs($user)
            ->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), $payload)
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertSame(1, PembayaranDetail::count());
        $this->assertSame(275000, (int) Pembayaran::first()->total_sudah_dibayar);
    }

    public function test_allows_second_payment_when_details_differ(): void
    {
        // Setoran kedua yang memang berbeda tidak boleh ikut terblokir.
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121283']);
        Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'harga' => 500000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        $this->actingAs($user)->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), [
            'nominal' => 200000,
            'keterangan_detail' => 'Angsuran pertama',
            'pembayaran_via' => 0,
            'tanggal_pembayaran' => now()->toDateString(),
        ])->assertOk();

        $this->actingAs($user)->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), [
            'nominal' => 300000,
            'keterangan_detail' => 'Angsuran kedua',
            'pembayaran_via' => 0,
            'tanggal_pembayaran' => now()->toDateString(),
        ])->assertOk();

        $this->assertSame(2, PembayaranDetail::count());
        $this->assertSame(500000, (int) Pembayaran::first()->total_sudah_dibayar);
    }

    public function test_allows_identical_payment_again_after_the_window_passes(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121284']);
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'harga' => 600000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        $payload = [
            'nominal' => 300000,
            'keterangan_detail' => 'Pembayaran LES',
            'pembayaran_via' => 0,
            'tanggal_pembayaran' => now()->toDateString(),
        ];

        $this->actingAs($user)
            ->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), $payload)
            ->assertOk();

        // Majukan waktu melewati jeda anti-ganda: setoran identik berikutnya sah.
        $this->travel(10)->minutes();

        $this->actingAs($user)
            ->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), $payload)
            ->assertOk();

        $this->assertSame(2, PembayaranDetail::count());
        $this->assertSame(600000, (int) $tagihan->fresh()->total_sudah_dibayar);
    }

    public function test_rejects_identical_free_form_invoice_created_twice(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121285']);

        $payload = [
            'id_siswa' => $siswa->id,
            'harga' => 75000,
            'keterangan' => 'Buku modul semester 1',
        ];

        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertSame(1, Pembayaran::count());
    }

    public function test_allows_different_free_form_invoices_back_to_back(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['no_hp' => '+6285640121286']);

        foreach (['Buku modul', 'Denda keterlambatan'] as $keterangan) {
            $this->actingAs($user)->postJson(route('admin.pembayaran.store'), [
                'id_siswa' => $siswa->id,
                'harga' => 75000,
                'keterangan' => $keterangan,
            ])->assertOk();
        }

        $this->assertSame(2, Pembayaran::count());
    }

    public function test_duplicate_guard_is_scoped_to_the_same_family(): void
    {
        // Dua keluarga berbeda yang kebetulan menyetor nominal sama di hari yang
        // sama tidak boleh saling memblokir.
        $user = User::factory()->create();
        $paket = Paket::create(['nama_paket' => 'Reguler', 'harga' => 300000, 'pertemuan' => 2]);

        foreach (['+6285640121287', '+6285640121288'] as $noHp) {
            $siswa = Siswa::factory()->create(['no_hp' => $noHp, 'paket_pembayaran' => $paket->id]);
            Pembayaran::create([
                'id_siswa' => $siswa->id,
                'no_hp' => $noHp,
                'harga' => 300000,
                'status' => 0,
                'total_sudah_dibayar' => 0,
            ]);

            $this->actingAs($user)->postJson(route('admin.pembayaran.bayarSiswa', $siswa->id), [
                'nominal' => 300000,
                'keterangan_detail' => 'Pembayaran LES',
                'pembayaran_via' => 0,
                'tanggal_pembayaran' => now()->toDateString(),
            ])->assertOk();
        }

        $this->assertSame(2, PembayaranDetail::count());
    }
}
