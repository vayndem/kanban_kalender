<?php

namespace Tests\Feature;

use App\Models\AbsensiGuru;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\ModulAjar;
use App\Models\ModulAjarDetail;
use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\PembayaranDetail;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * jadwals memakai onDelete('cascade') ke guru/ruang/mapel/sesi. Tanpa
 * penjagaan di controller, menghapus satu guru yang sedang mengajar berarti
 * seluruh baris jadwalnya ikut lenyap tanpa peringatan. Tes ini mengunci
 * penjaga yang mencegah itu, plus dua celah update/delete lain yang
 * ditemukan sekalian: harga tagihan bisa diturunkan di bawah yang sudah
 * dibayar, invoice bisa dipindah kepemilikan walau sudah punya riwayat
 * setoran, dan paket bisa dihapus walau masih dipakai siswa.
 */
class PerlindunganHapusDanUbahTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function buatJadwal(): array
    {
        $guru = Guru::factory()->create();
        $ruang = Ruang::factory()->create();
        $mapel = MataPelajaran::factory()->create();
        $sesi = Sesi::factory()->create();
        $siswa = Siswa::factory()->create();
        $hari = Hari::factory()->create(['name' => 'Senin'.uniqid()]);

        Jadwal::create([
            'siswa_id' => $siswa->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'hari_id' => $hari->id,
            'ruang_id' => $ruang->id,
            'sesi_id' => $sesi->id,
        ]);

        return compact('guru', 'ruang', 'mapel', 'sesi', 'siswa', 'hari');
    }

    public function test_guru_with_active_schedule_cannot_be_deleted(): void
    {
        ['guru' => $guru] = $this->buatJadwal();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.guru.destroy', $guru->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('gurus', ['id' => $guru->id]);
        $this->assertDatabaseCount('jadwals', 1);
    }

    public function test_guru_without_schedule_can_still_be_deleted(): void
    {
        $guru = Guru::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.guru.destroy', $guru->id))
            ->assertOk();

        $this->assertDatabaseMissing('gurus', ['id' => $guru->id]);
    }

    public function test_guru_with_teaching_attendance_history_cannot_be_deleted_even_without_a_schedule(): void
    {
        // Guru bisa saja sudah ditarik dari semua jadwal (0 baris di jadwals),
        // tapi masih punya riwayat absen mengajar di Modul Ajar -- absensi_gurus
        // tidak punya onDelete cascade, jadi delete mentah akan gagal di level DB.
        $guru = Guru::factory()->create();
        $modulAjar = ModulAjar::create([
            'kode_kelas' => 'kode-riwayat',
            'tujuan_pembelajaran' => 'x', 'kompetensi_awal' => 'x', 'model_pembelajaran' => 'x', 'sarana_media' => 'x',
        ]);
        $detail = ModulAjarDetail::create(['modul_ajar_id' => $modulAjar->id, 'materi' => 'Materi Lama']);
        AbsensiGuru::create(['guru_id' => $guru->id, 'modul_ajar_detail_id' => $detail->id, 'tanggal' => now()]);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.guru.destroy', $guru->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('gurus', ['id' => $guru->id]);
    }

    public function test_ruang_with_active_schedule_cannot_be_deleted(): void
    {
        ['ruang' => $ruang] = $this->buatJadwal();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.ruang.destroy', $ruang->id))
            ->assertStatus(422);

        $this->assertDatabaseCount('jadwals', 1);
    }

    public function test_mapel_with_active_schedule_cannot_be_deleted(): void
    {
        ['mapel' => $mapel] = $this->buatJadwal();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.mapel.destroy', $mapel->id))
            ->assertStatus(422);

        $this->assertDatabaseCount('jadwals', 1);
    }

    public function test_sesi_with_active_schedule_cannot_be_deleted(): void
    {
        ['sesi' => $sesi] = $this->buatJadwal();

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.sesi.destroy', $sesi->id))
            ->assertStatus(422);

        $this->assertDatabaseCount('jadwals', 1);
    }

    public function test_paket_in_use_cannot_be_deleted(): void
    {
        $paket = Paket::create(['nama_paket' => 'Reguler', 'harga' => 100000, 'pertemuan' => 2]);
        Siswa::factory()->create(['paket_pembayaran' => $paket->id]);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.paket.destroy', $paket->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('pakets', ['id' => $paket->id]);
    }

    public function test_paket_used_only_as_secondary_package_still_blocks_deletion(): void
    {
        // Kolom paket_pembayaran_2..5 bukan cuma paket_pembayaran utama --
        // guard harus mengecek semuanya, bukan cuma kolom pertama.
        $paket = Paket::create(['nama_paket' => 'Tambahan', 'harga' => 50000, 'pertemuan' => 1]);
        Siswa::factory()->create(['paket_pembayaran_3' => $paket->id]);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.paket.destroy', $paket->id))
            ->assertStatus(422);

        $this->assertDatabaseHas('pakets', ['id' => $paket->id]);
    }

    public function test_paket_not_in_use_can_be_deleted(): void
    {
        $paket = Paket::create(['nama_paket' => 'Kosong', 'harga' => 100000, 'pertemuan' => 2]);

        $this->actingAs($this->admin())
            ->deleteJson(route('admin.paket.destroy', $paket->id))
            ->assertOk();

        $this->assertDatabaseMissing('pakets', ['id' => $paket->id]);
    }

    public function test_invoice_price_cannot_be_lowered_below_amount_already_paid(): void
    {
        $siswa = Siswa::factory()->create();
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswa->id, 'no_hp' => $siswa->no_hp,
            'harga' => 300000, 'status' => 1, 'total_sudah_dibayar' => 200000,
        ]);

        $this->actingAs($this->admin())->putJson(route('admin.pembayaran.update', $tagihan->id), [
            'id_siswa' => $siswa->id,
            'harga' => 150000,
            'keterangan' => 'Coba turunkan',
        ])->assertStatus(422);

        $this->assertSame(300000, (int) $tagihan->fresh()->harga);
    }

    public function test_invoice_price_can_be_raised_or_kept_equal_to_amount_paid(): void
    {
        $siswa = Siswa::factory()->create();
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswa->id, 'no_hp' => $siswa->no_hp,
            'harga' => 300000, 'status' => 1, 'total_sudah_dibayar' => 200000,
        ]);

        $this->actingAs($this->admin())->putJson(route('admin.pembayaran.update', $tagihan->id), [
            'id_siswa' => $siswa->id,
            'harga' => 200000,
            'keterangan' => 'Koreksi nominal',
        ])->assertOk();

        $this->assertSame(200000, (int) $tagihan->fresh()->harga);
    }

    public function test_invoice_with_payment_history_cannot_be_reassigned_to_another_student(): void
    {
        $siswaLama = Siswa::factory()->create();
        $siswaBaru = Siswa::factory()->create();
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswaLama->id, 'no_hp' => $siswaLama->no_hp,
            'harga' => 300000, 'status' => 1, 'total_sudah_dibayar' => 100000,
        ]);
        PembayaranDetail::create(['id_pembayaran' => $tagihan->id, 'pembayaran' => 100000, 'keterangan' => 'Setoran pertama']);

        $this->actingAs($this->admin())->putJson(route('admin.pembayaran.update', $tagihan->id), [
            'id_siswa' => $siswaBaru->id,
            'harga' => 300000,
            'keterangan' => 'Coba pindah siswa',
        ])->assertStatus(422);

        $this->assertSame($siswaLama->id, $tagihan->fresh()->id_siswa);
    }

    public function test_invoice_without_payment_history_can_still_be_reassigned(): void
    {
        // Salah pilih siswa sebelum ada uang masuk sama sekali harus tetap
        // bisa dikoreksi -- guard ini hanya menahan kasus yang sudah ada uang.
        $siswaLama = Siswa::factory()->create();
        $siswaBaru = Siswa::factory()->create();
        $tagihan = Pembayaran::create([
            'id_siswa' => $siswaLama->id, 'no_hp' => $siswaLama->no_hp,
            'harga' => 300000, 'status' => 0, 'total_sudah_dibayar' => 0,
        ]);

        $this->actingAs($this->admin())->putJson(route('admin.pembayaran.update', $tagihan->id), [
            'id_siswa' => $siswaBaru->id,
            'harga' => 300000,
            'keterangan' => 'Koreksi siswa yang salah pilih',
        ])->assertOk();

        $this->assertSame($siswaBaru->id, $tagihan->fresh()->id_siswa);
    }
}
