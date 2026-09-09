<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\ModulAjar;
use App\Models\ModulAjarDetail;
use App\Models\Penggajian;
use App\Models\User;
use App\Services\PayrollService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PayrollTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payroll;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->payroll = app(PayrollService::class);
    }

    private function guru(int $bawaan = 1_000_000, int $perKehadiran = 50_000): Guru
    {
        return Guru::factory()->create([
            'gaji_bawaan' => $bawaan,
            'gaji_per_kehadiran' => $perKehadiran,
        ]);
    }

    private function catatKehadiran(Guru $guru, int $jumlah, string $materi = 'Materi'): void
    {
        $modulAjar = ModulAjar::firstOrCreate(
            ['kode_kelas' => 'kelas-'.$guru->id],
            ['tujuan_pembelajaran' => 'x', 'kompetensi_awal' => 'x', 'model_pembelajaran' => 'x', 'sarana_media' => 'x']
        );

        for ($i = 0; $i < $jumlah; $i++) {
            $detail = ModulAjarDetail::create([
                'modul_ajar_id' => $modulAjar->id,
                'materi' => $materi.' '.($i + 1),
                'tanggal_diajarkan' => now()->toDateString(),
                'diajarkan_oleh_guru_id' => $guru->id,
            ]);

            AbsensiGuru::create([
                'guru_id' => $guru->id,
                'modul_ajar_detail_id' => $detail->id,
                'tanggal' => now()->toDateString(),
            ]);
        }
    }

    private function kehadiranBelumDibayar(Guru $guru): int
    {
        return AbsensiGuru::where('guru_id', $guru->id)->whereNull('penggajian_id')->count();
    }

    public function test_total_dihitung_dari_gaji_bawaan_ditambah_kehadiran(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $this->catatKehadiran($guru, 7);

        $struk = $this->payroll->jalankan($guru);

        $this->assertSame(7, $struk->jumlah_kehadiran);
        $this->assertSame(1_350_000, $struk->total, '1.000.000 + (7 x 50.000)');
    }

    public function test_menjalankan_penggajian_mereset_hitungan_kehadiran_ke_nol(): void
    {
        $guru = $this->guru();
        $this->catatKehadiran($guru, 4);

        $this->assertSame(4, $this->kehadiranBelumDibayar($guru));

        $this->payroll->jalankan($guru);

        $this->assertSame(0, $this->kehadiranBelumDibayar($guru), 'Hitungan berjalan harus kembali nol.');
    }

    public function test_riwayat_kehadiran_tidak_dihapus_hanya_ditutup(): void
    {
        $guru = $this->guru();
        $this->catatKehadiran($guru, 3);

        $struk = $this->payroll->jalankan($guru);

        $this->assertSame(3, AbsensiGuru::where('guru_id', $guru->id)->count(), 'Barisnya harus tetap ada.');
        $this->assertSame(3, AbsensiGuru::where('penggajian_id', $struk->id)->whereNotNull('ditutup_pada')->count());
    }

    public function test_struk_membekukan_tarif_sehingga_kenaikan_gaji_tidak_mengubah_masa_lalu(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $this->catatKehadiran($guru, 2);

        $struk = $this->payroll->jalankan($guru);
        $totalAwal = $struk->total;

        $guru->update(['gaji_bawaan' => 5_000_000, 'gaji_per_kehadiran' => 999_000]);

        $this->assertSame($totalAwal, $struk->fresh()->total, 'Struk lama tidak boleh ikut berubah.');
        $this->assertSame(1_000_000, $struk->fresh()->gaji_bawaan);
        $this->assertSame(50_000, $struk->fresh()->gaji_per_kehadiran);
    }

    public function test_guru_tanpa_kehadiran_tetap_menerima_gaji_bawaan(): void
    {
        $guru = $this->guru(800_000, 50_000);

        $struk = $this->payroll->jalankan($guru);

        $this->assertSame(0, $struk->jumlah_kehadiran);
        $this->assertSame(800_000, $struk->total);
    }

    public function test_klik_ganda_tidak_menerbitkan_struk_kedua(): void
    {
        $guru = $this->guru();
        $this->catatKehadiran($guru, 2);

        $this->payroll->jalankan($guru);

        $this->expectException(RuntimeException::class);
        $this->payroll->jalankan($guru);
    }

    public function test_membatalkan_struk_melepas_kehadirannya_untuk_dihitung_lagi(): void
    {
        $guru = $this->guru();
        $this->catatKehadiran($guru, 5);
        $struk = $this->payroll->jalankan($guru);

        $this->assertSame(0, $this->kehadiranBelumDibayar($guru));

        $dibatalkan = $this->payroll->batalkan($struk, null, 'Salah tarif');

        $this->assertTrue($dibatalkan->sudahDibatalkan());
        $this->assertSame(5, $this->kehadiranBelumDibayar($guru), 'Kehadiran harus kembali dihitung.');
        $this->assertSame('Salah tarif', $dibatalkan->alasan_batal);
    }

    public function test_struk_yang_dibatalkan_tidak_bisa_dibatalkan_dua_kali(): void
    {
        $guru = $this->guru();
        $struk = $this->payroll->jalankan($guru);
        $this->payroll->batalkan($struk);

        $this->expectException(RuntimeException::class);
        $this->payroll->batalkan($struk->fresh());
    }

    public function test_membatalkan_struk_membuka_jalan_untuk_menjalankan_ulang(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $this->catatKehadiran($guru, 3);

        $struk = $this->payroll->jalankan($guru);
        $this->payroll->batalkan($struk);

        $ulang = $this->payroll->jalankan($guru);

        $this->assertSame(3, $ulang->jumlah_kehadiran, 'Kehadiran yang dilepas harus terhitung lagi.');
        $this->assertSame(1_150_000, $ulang->total);
    }

    public function test_jalankan_semua_menerbitkan_satu_struk_per_guru(): void
    {
        $a = $this->guru(1_000_000, 10_000);
        $b = $this->guru(2_000_000, 20_000);
        $this->catatKehadiran($a, 3);

        $hasil = $this->payroll->jalankanSemua();

        $this->assertCount(2, $hasil['struk']);
        $this->assertCount(0, $hasil['dilewati']);
        $this->assertSame(1_030_000, Penggajian::where('guru_id', $a->id)->first()->total);
        $this->assertSame(2_000_000, Penggajian::where('guru_id', $b->id)->first()->total);
    }

    public function test_ringkasan_menampilkan_perkiraan_total_sebelum_dijalankan(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $this->catatKehadiran($guru, 6);

        $baris = collect($this->payroll->ringkasan())->firstWhere('id', $guru->id);

        $this->assertSame(6, $baris['kehadiran_belum_dibayar']);
        $this->assertSame(1_300_000, $baris['perkiraan_total']);
        $this->assertNull($baris['struk_terakhir']);
    }

    public function test_log_kelas_mencatat_materi_yang_masuk_struk(): void
    {
        $guru = $this->guru();
        $this->catatKehadiran($guru, 2, 'Perkalian');
        $struk = $this->payroll->jalankan($guru);

        $log = $this->payroll->logKelas($struk);

        $this->assertCount(2, $log);
        $this->assertSame('Perkalian 1', $log[0]['materi']);
    }

    public function test_kehadiran_baru_setelah_penggajian_hanya_masuk_struk_berikutnya(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $this->catatKehadiran($guru, 2);
        $pertama = $this->payroll->jalankan($guru);

        $this->catatKehadiran($guru, 3, 'Materi Baru');
        $this->travel(PayrollService::JEDA_ANTI_GANDA + 1)->seconds();
        $kedua = $this->payroll->jalankan($guru);

        $this->assertSame(2, $pertama->fresh()->jumlah_kehadiran);
        $this->assertSame(3, $kedua->jumlah_kehadiran);
    }

    public function test_halaman_payroll_tampil_dengan_tarif_dan_perkiraan(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $guru->update(['name' => 'Bu Sinta']);
        $this->catatKehadiran($guru, 4);

        $respon = $this->actingAs(User::factory()->create())->get(route('admin.payroll.index'));

        $respon->assertOk()
            ->assertSee('Payroll Guru')
            ->assertSee('Siap Semua')
            ->assertSee('Bu Sinta');
    }

    public function test_menjalankan_penggajian_lewat_route_menerbitkan_struk(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $this->catatKehadiran($guru, 2);

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.payroll.jalankan', $guru->id))
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame(1_100_000, Penggajian::where('guru_id', $guru->id)->first()->total);
        $this->assertSame(0, $this->kehadiranBelumDibayar($guru));
    }

    public function test_route_pembatalan_menolak_alasan_kosong(): void
    {
        $guru = $this->guru();
        $struk = $this->payroll->jalankan($guru);

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.payroll.batalkan', $struk->id), [])
            ->assertStatus(422);

        $this->assertFalse($struk->fresh()->sudahDibatalkan());
    }

    public function test_tarif_negatif_ditolak(): void
    {
        $guru = $this->guru();

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.payroll.updateTarif', $guru->id), [
                'gaji_bawaan' => -1,
                'gaji_per_kehadiran' => 10,
            ])
            ->assertStatus(422);
    }

    public function test_ringkasan_menampilkan_kewajiban_gaji_berjalan(): void
    {
        $guru = $this->guru(1_000_000, 50_000);
        $guru->update(['name' => 'Bu Sinta']);
        $this->catatKehadiran($guru, 4);

        $respon = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']));

        $respon->assertOk()
            ->assertSee('Gaji Guru Berjalan')
            ->assertSee('Bu Sinta');
    }

    public function test_gaji_berjalan_hilang_dari_ringkasan_setelah_digaji(): void
    {
        $guru = $this->guru();
        $guru->update(['name' => 'Bu Sinta']);
        $this->catatKehadiran($guru, 4);
        $this->payroll->jalankan($guru);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'ringkasan']))
            ->assertOk()
            ->assertSee('Tidak ada kehadiran yang menunggu digaji.');
    }

    public function test_guru_hanya_melihat_penggajian_miliknya_sendiri(): void
    {
        $guruA = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test', 'gaji_bawaan' => 900_000, 'gaji_per_kehadiran' => 40_000]);
        $guruB = $this->guru(5_000_000, 500_000);
        $guruB->update(['name' => 'Pak Rahasia']);
        $this->catatKehadiran($guruA, 2);
        $this->catatKehadiran($guruB, 9);

        $userA = User::factory()->guru($guruA)->create();

        $respon = $this->actingAs($userA)->get(route('guru.gaji'));

        $respon->assertOk()
            ->assertSee('Bu Rina')
            ->assertSee('Gaji Saya')
            ->assertDontSee('Pak Rahasia');
    }

    public function test_guru_tidak_bisa_mengunduh_struk_guru_lain(): void
    {
        $guruA = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test']);
        $guruB = $this->guru();
        $strukB = $this->payroll->jalankan($guruB);

        $userA = User::factory()->guru($guruA)->create();

        $this->actingAs($userA)
            ->get(route('penggajian.strukPdf', $strukB->id))
            ->assertForbidden();
    }

    public function test_guru_bisa_mengunduh_struknya_sendiri(): void
    {
        $guru = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test', 'gaji_bawaan' => 700_000, 'gaji_per_kehadiran' => 0]);
        $struk = $this->payroll->jalankan($guru);
        $user = User::factory()->guru($guru)->create();

        $respon = $this->actingAs($user)->get(route('penggajian.strukPdf', $struk->id));

        $respon->assertOk();
        $this->assertSame('application/pdf', $respon->headers->get('content-type'));
    }

    public function test_admin_bisa_mengunduh_struk_siapa_pun(): void
    {
        $guru = $this->guru();
        $this->catatKehadiran($guru, 3);
        $struk = $this->payroll->jalankan($guru);

        $respon = $this->actingAs(User::factory()->create())
            ->get(route('penggajian.strukPdf', $struk->id));

        $respon->assertOk();
        $this->assertSame('application/pdf', $respon->headers->get('content-type'));
    }

    public function test_guru_tanpa_data_guru_diarahkan_ke_halaman_belum_tertaut(): void
    {
        $user = User::factory()->tanpaPeran()->create();
        $user->assignRole('guru');

        $this->actingAs($user)->get(route('guru.gaji'))
            ->assertOk()
            ->assertSee('belum tertaut');
    }

    public function test_guru_tidak_boleh_mengakses_payroll(): void
    {
        $guru = Guru::create(['name' => 'Bu Rina', 'email' => 'rina@eling.test']);
        $userGuru = User::factory()->guru($guru)->create();

        $this->actingAs($userGuru)->get(route('admin.payroll.index'))->assertForbidden();
    }
}
