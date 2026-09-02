<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\PaymentBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduleAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_is_created_atomically_for_selected_students(): void
    {
        [$user, $hari, $sesi, $mapel, $guru, $ruang] = $this->scheduleMasters();
        $students = Siswa::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson(route('admin.jadwal.store'), [
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_ids' => $students->pluck('id')->all(),
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertDatabaseCount('jadwals', 2);
    }

    public function test_teacher_room_and_student_conflicts_are_rejected(): void
    {
        [$user, $hari, $sesi, $mapel, $guru, $ruang] = $this->scheduleMasters();
        $student = Siswa::factory()->create();
        Jadwal::create([
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_id' => $student->id,
        ]);

        $otherMapel = MataPelajaran::factory()->create(['name' => 'Matematika']);
        $otherStudent = Siswa::factory()->create();
        $response = $this->actingAs($user)->postJson(route('admin.jadwal.store'), [
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => $otherMapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_ids' => [$otherStudent->id],
        ]);

        $response->assertStatus(422)->assertJsonPath('status', 'error');
        $this->assertDatabaseCount('jadwals', 1);
    }

    public function test_plus_62_phone_is_preserved_and_payment_is_allocated_without_rounding(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281234567890']);
        $this->assertSame('+6281234567890', $student->fresh()->no_hp);

        $first = Pembayaran::create(['id_siswa' => $student->id, 'no_hp' => $student->no_hp, 'harga' => 100000, 'status' => 0, 'total_sudah_dibayar' => 0]);
        $second = Pembayaran::create(['id_siswa' => $student->id, 'no_hp' => $student->no_hp, 'harga' => 100000, 'status' => 0, 'total_sudah_dibayar' => 0]);

        $response = $this->actingAs($user)->postJson(route('admin.pembayaran.bayarSiswa', $student->id), [
            'nominal' => 150001,
            'pembayaran_via' => 1,
            'tanggal_pembayaran' => now()->toDateString(),
            'keterangan_detail' => 'Tes bayar',
        ]);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame(100000, (int) $first->fresh()->total_sudah_dibayar);
        $this->assertSame(50001, (int) $second->fresh()->total_sudah_dibayar);
        $this->assertSame(150001, (int) $first->details()->sum('pembayaran') + (int) $second->details()->sum('pembayaran'));
    }

    public function test_public_calendar_can_be_opened(): void
    {
        $this->get(route('jadwal.kalender'))->assertOk()->assertSee('Kalender Jadwal');
    }

    public function test_overpayment_is_rejected_without_changing_balances(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+628111111111']);
        $invoice = Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 50000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        $this->actingAs($user)->postJson(route('admin.pembayaran.bayarSiswa', $student->id), [
            'nominal' => 50001,
            'pembayaran_via' => 0,
            'tanggal_pembayaran' => now()->toDateString(),
        ])->assertStatus(422);

        $this->assertSame(0, (int) $invoice->fresh()->total_sudah_dibayar);
        $this->assertDatabaseCount('pembayaran_details', 0);
    }

    public function test_lunas_semua_settles_remaining_balances_and_creates_system_details(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000001']);

        $first = Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 100000,
            'status' => 0,
            'total_sudah_dibayar' => 25000,
        ]);

        $second = Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 80000,
            'status' => 1,
            'total_sudah_dibayar' => 80000,
        ]);

        $this->actingAs($user)
            ->postJson(route('admin.pembayaran.lunasSemua'))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(100000, (int) $first->fresh()->total_sudah_dibayar);
        $this->assertSame(2, (int) $first->fresh()->status);
        $this->assertSame(2, (int) $second->fresh()->status);
        $this->assertDatabaseHas('pembayaran_details', [
            'id_pembayaran' => $first->id,
            'pembayaran' => 75000,
            'keterangan' => 'Selesai sistem',
        ]);
    }

    public function test_paid_receipt_can_be_rendered_without_logo_file(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000002']);
        $invoice = Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 50000,
            'status' => 2,
            'total_sudah_dibayar' => 50000,
            'tanggal_pembayaran' => now()->toDateString(),
            'pembayaran_via' => 0,
        ]);

        PembayaranDetail::create([
            'id_pembayaran' => $invoice->id,
            'pembayaran' => 50000,
            'keterangan' => 'Pelunasan',
        ]);

        $this->actingAs($user)
            ->get(route('admin.pembayaran.struk', ['no_hp' => $student->no_hp]))
            ->assertOk();
    }

    public function test_payment_family_detail_is_loaded_from_dedicated_endpoint(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000008']);

        $first = Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 100000,
            'status' => 1,
            'keterangan' => 'Tagihan A',
            'total_sudah_dibayar' => 25000,
        ]);

        $second = Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 50000,
            'status' => 2,
            'keterangan' => 'Tagihan B',
            'total_sudah_dibayar' => 50000,
            'tanggal_pembayaran' => now()->toDateString(),
            'pembayaran_via' => 0,
        ]);

        PembayaranDetail::create([
            'id_pembayaran' => $first->id,
            'pembayaran' => 25000,
            'keterangan' => 'Cicilan 1',
        ]);

        PembayaranDetail::create([
            'id_pembayaran' => $second->id,
            'pembayaran' => 50000,
            'keterangan' => 'Pelunasan',
        ]);

        $response = $this->actingAs($user)->getJson(route('admin.pembayaran.detailKeluarga', [
            'no_hp' => $student->no_hp,
            'ids' => $first->id . ',' . $second->id,
        ]));

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertCount(2, $response->json('data.raw_items'));
        $this->assertCount(2, $response->json('data.payment_details'));
        $this->assertSame(150000, $response->json('data.total_harga'));
        $this->assertSame(75000, $response->json('data.total_sudah_dibayar'));
    }

    public function test_penagihan_massal_skips_duplicate_invoices_for_same_period(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create([
            'no_hp' => '+6281230000003',
            'paket_pembayaran' => null,
        ]);

        $paket = \App\Models\Paket::create([
            'nama_paket' => 'Paket A',
            'harga' => 150000,
            'pertemuan' => 4,
        ]);

        $student->update(['paket_pembayaran' => $paket->id]);

        $this->actingAs($user)->postJson(route('admin.pembayaran.penagihanMassal'))->assertOk();

        // Penagihan massal kedua di bulan yang sama ditolak oleh kunci periode.
        $this->actingAs($user)->postJson(route('admin.pembayaran.penagihanMassal'))
            ->assertStatus(409)
            ->assertJsonPath('status', 'error');

        $bulanTahun = now()->translatedFormat('F Y');
        $this->assertSame(1, Pembayaran::where('id_siswa', $student->id)
            ->where('keterangan', "Tagihan Paket {$paket->nama_paket} - {$bulanTahun}")
            ->count());
    }

    public function test_manual_invoice_with_package_blocks_duplicate_from_mass_billing(): void
    {
        // Regresi untuk bug tagihan ganda: admin membuat tagihan manual untuk
        // sebuah paket, lalu menjalankan penagihan massal. Sebelum perbaikan,
        // keduanya lolos karena teks keterangannya berbeda ("Pembayaran Paket X
        // (3 Pertemuan)" vs "Tagihan Paket X - September 2026"), sehingga siswa
        // tertagih dua kali untuk paket dan bulan yang sama.
        $user = User::factory()->create();
        $paket = \App\Models\Paket::create([
            'nama_paket' => 'TKA SD/SMP 3X/Minggu',
            'harga' => 350000,
            'pertemuan' => 3,
        ]);
        $student = Siswa::factory()->create([
            'no_hp' => '+6285602140827',
            'paket_pembayaran' => $paket->id,
        ]);

        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), [
            'id_siswa' => $student->id,
            'id_paket' => $paket->id,
            'harga' => $paket->harga,
            'keterangan' => "Pembayaran Paket {$paket->nama_paket} ({$paket->pertemuan} Pertemuan)",
        ])->assertOk();

        $this->actingAs($user)->postJson(route('admin.pembayaran.penagihanMassal'))->assertOk();

        $this->assertSame(1, Pembayaran::where('id_siswa', $student->id)->count());
        $this->assertSame(350000, (int) Pembayaran::where('id_siswa', $student->id)->sum('harga'));
    }

    public function test_manual_invoice_rejects_duplicate_package_in_same_period(): void
    {
        $user = User::factory()->create();
        $paket = \App\Models\Paket::create([
            'nama_paket' => 'Paket Duplikat',
            'harga' => 200000,
            'pertemuan' => 4,
        ]);
        $student = Siswa::factory()->create(['no_hp' => '+6281230000009']);

        $payload = [
            'id_siswa' => $student->id,
            'id_paket' => $paket->id,
            'harga' => $paket->harga,
            'keterangan' => 'Tagihan pertama',
        ];

        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), $payload)->assertOk();
        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertSame(1, Pembayaran::where('id_siswa', $student->id)->count());
    }

    public function test_manual_invoice_without_package_stays_unrestricted(): void
    {
        // Tagihan bebas (tanpa paket) tidak ikut aturan anti-ganda, karena
        // memang bisa sah dibuat berkali-kali: denda, buku, kegiatan, dll.
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000010']);

        foreach (['Buku modul', 'Biaya kegiatan'] as $keterangan) {
            $this->actingAs($user)->postJson(route('admin.pembayaran.store'), [
                'id_siswa' => $student->id,
                'harga' => 50000,
                'keterangan' => $keterangan,
            ])->assertOk();
        }

        $this->assertSame(2, Pembayaran::where('id_siswa', $student->id)->count());
    }

    public function test_global_settlement_is_locked_once_per_month(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000011']);
        Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 100000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        $this->actingAs($user)->postJson(route('admin.pembayaran.lunasSemua'))->assertOk();

        Pembayaran::create([
            'id_siswa' => $student->id,
            'no_hp' => $student->no_hp,
            'harga' => 250000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);

        // Klik kedua di bulan yang sama ditolak, sehingga tagihan baru yang
        // belum dibayar tidak ikut tersapu jadi "lunas" tanpa uang masuk.
        $this->actingAs($user)->postJson(route('admin.pembayaran.lunasSemua'))
            ->assertStatus(409)
            ->assertJsonPath('status', 'error');

        $this->assertSame(0, Pembayaran::where('harga', 250000)->first()->status);
    }

    public function test_per_family_settlement_is_not_locked_by_monthly_rule(): void
    {
        // Pelunasan per keluarga adalah aksi harian, bukan tutup buku bulanan,
        // jadi tidak boleh ikut terkunci.
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000012']);

        foreach ([100000, 150000] as $harga) {
            Pembayaran::create([
                'id_siswa' => $student->id,
                'no_hp' => $student->no_hp,
                'harga' => $harga,
                'status' => 0,
                'total_sudah_dibayar' => 0,
            ]);

            $this->actingAs($user)
                ->postJson(route('admin.pembayaran.keLunasMassal', $student->id))
                ->assertOk();
        }

        $this->assertSame(0, Pembayaran::whereIn('status', [0, 1])->count());
    }

    public function test_period_lock_is_released_when_batch_produces_nothing(): void
    {
        // Kalau penagihan massal tidak menghasilkan tagihan apa pun (mis. paket
        // belum dipasang ke siswa), periode tidak boleh ikut terkunci -- admin
        // harus tetap bisa mengulang setelah membenahi data paket.
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('admin.pembayaran.penagihanMassal'))->assertOk();

        $paket = \App\Models\Paket::create([
            'nama_paket' => 'Paket Menyusul',
            'harga' => 120000,
            'pertemuan' => 4,
        ]);
        Siswa::factory()->create([
            'no_hp' => '+6281230000013',
            'paket_pembayaran' => $paket->id,
        ]);

        $this->actingAs($user)->postJson(route('admin.pembayaran.penagihanMassal'))->assertOk();

        $this->assertDatabaseCount('pembayarans', 1);
    }

    public function test_mass_billing_uses_bounded_queries_for_many_students(): void
    {
        $package = \App\Models\Paket::create([
            'nama_paket' => 'Paket Batch',
            'harga' => 175000,
            'pertemuan' => 4,
        ]);
        Siswa::factory()->count(30)->create(['paket_pembayaran' => $package->id]);

        DB::enableQueryLog();
        $created = app(PaymentBatchService::class)->createMonthlyInvoices();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(30, $created);
        $this->assertDatabaseCount('pembayarans', 30);
        $this->assertLessThanOrEqual(10, $queryCount);
    }

    public function test_bulk_student_archive_uses_bounded_queries(): void
    {
        $user = User::factory()->create();
        $students = Siswa::factory()->count(20)->create();

        DB::enableQueryLog();
        $this->actingAs($user)
            ->deleteJson(route('admin.siswa.destroy', $students->pluck('id')->join(',')))
            ->assertOk()
            ->assertJsonPath('status', 'success');
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertDatabaseCount('siswas', 0);
        $this->assertDatabaseCount('arsips', 20);
        $this->assertLessThanOrEqual(12, $queryCount);
    }

    public function test_store_payment_invoice_forces_unpaid_status(): void
    {
        $user = User::factory()->create();
        $student = Siswa::factory()->create(['no_hp' => '+6281230000004']);

        $this->actingAs($user)->postJson(route('admin.pembayaran.store'), [
            'id_siswa' => $student->id,
            'harga' => 125000,
            'keterangan' => 'Tagihan uji',
            'status' => 2,
        ])->assertOk();

        $invoice = Pembayaran::where('id_siswa', $student->id)->latest('id')->first();
        $this->assertSame(0, (int) $invoice->status);
        $this->assertSame(0, (int) $invoice->total_sudah_dibayar);
    }

    public function test_dashboard_renders_only_the_requested_tab_payload(): void
    {
        $user = User::factory()->create();

        $jadwal = $this->actingAs($user)->get(route('dashboard', ['tab' => 'jadwal']));
        $jadwal->assertOk()->assertSee('Jadwal Pelajaran')->assertDontSee('Ringkasan Tagihan Siswa');

        $students = $this->actingAs($user)->get(route('dashboard', ['tab' => 'data_siswa']));
        $students->assertOk()->assertSee('Data Master Siswa')->assertDontSee('Ringkasan Tagihan Siswa');

        $payments = $this->actingAs($user)->get(route('dashboard', ['tab' => 'pembayaran']));
        $payments->assertOk()->assertSee('Administrasi Pembayaran Siswa')->assertDontSee('Data Master Siswa');

        $this->assertLessThan(1_000_000, strlen($jadwal->getContent()));
        $this->assertLessThan(1_000_000, strlen($students->getContent()));
        $this->assertLessThan(1_000_000, strlen($payments->getContent()));
    }

    public function test_whatsapp_schedule_groups_a_student_schedule_by_day(): void
    {
        [$user, $monday, $session, $subject, $teacher, $room] = $this->scheduleMasters();
        $tuesday = Hari::create(['name' => 'Selasa']);
        $student = Siswa::factory()->create(['name' => 'Haikal Pratama', 'panggilan' => 'Haikal', 'kelas' => '8A']);

        foreach ([$monday, $tuesday] as $day) {
            Jadwal::create([
                'hari_id' => $day->id,
                'sesi_id' => $session->id,
                'mata_pelajaran_id' => $subject->id,
                'guru_id' => $teacher->id,
                'ruang_id' => $room->id,
                'siswa_id' => $student->id,
            ]);
        }

        $response = $this->actingAs($user)->getJson(route('admin.jadwal.generateText', ['search' => 'haikal']));

        $response->assertOk()->assertJsonPath('status', 'success');
        $text = $response->json('text');
        $this->assertStringContainsString('*SENIN*', $text);
        $this->assertStringContainsString('*SELASA*', $text);
        $this->assertStringContainsString('_Jadwal untuk: Haikal_', $text);
        $this->assertLessThan(strpos($text, '*SELASA*'), strpos($text, '*SENIN*'));
    }

    private function scheduleMasters(): array
    {
        return [
            User::factory()->create(),
            Hari::create(['name' => 'Senin']),
            Sesi::factory()->create(['name' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '09:00']),
            MataPelajaran::factory()->create(['name' => 'Fisika']),
            Guru::factory()->create(),
            Ruang::factory()->create(),
        ];
    }
}
