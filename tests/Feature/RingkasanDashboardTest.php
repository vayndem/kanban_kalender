<?php

namespace Tests\Feature;

use App\Models\Arsip;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Pembayaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RingkasanDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_defaults_to_ringkasan_tab_when_no_tab_is_specified(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertSee('Ringkasan Hari Ini');
    }

    public function test_ringkasan_tab_summarizes_today_and_flags_back_to_back_teaching(): void
    {
        $user = User::factory()->create();
        $hari = $this->hariHariIni();
        $mapel = MataPelajaran::factory()->create();
        $guru = Guru::factory()->create(['name' => 'Bu Sinta']);
        $ruang = Ruang::factory()->create();
        $siswa = Siswa::factory()->create();

        $sesis = [
            Sesi::factory()->create(['name' => 'Sesi 1', 'start_time' => '08:00', 'end_time' => '09:00']),
            Sesi::factory()->create(['name' => 'Sesi 2', 'start_time' => '09:00', 'end_time' => '10:00']),
            Sesi::factory()->create(['name' => 'Sesi 3', 'start_time' => '10:00', 'end_time' => '11:00']),
        ];

        foreach ($sesis as $sesi) {
            Jadwal::create([
                'hari_id' => $hari->id,
                'sesi_id' => $sesi->id,
                'mata_pelajaran_id' => $mapel->id,
                'guru_id' => $guru->id,
                'ruang_id' => $ruang->id,
                'siswa_id' => $siswa->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan']));

        $response->assertOk()
            ->assertSee('Ringkasan Hari Ini')
            ->assertSee('Bu Sinta')
            ->assertSee('3 sesi beruntun');
    }

    public function test_room_occupancy_flags_a_room_used_far_more_than_others(): void
    {
        $user = User::factory()->create();
        $hariSenin = Hari::create(['name' => 'Senin']);
        $hariSelasa = Hari::create(['name' => 'Selasa']);
        $mapel = MataPelajaran::factory()->create();
        $guru = Guru::factory()->create();
        $ruangSibuk = Ruang::factory()->create(['name' => 'Ruang Sibuk']);
        Ruang::factory()->create(['name' => 'Ruang Sepi']);
        $sesi1 = Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00']);
        $sesi2 = Sesi::factory()->create(['start_time' => '09:00', 'end_time' => '10:00']);

        foreach ([[$hariSenin, $sesi1], [$hariSenin, $sesi2], [$hariSelasa, $sesi1], [$hariSelasa, $sesi2]] as [$hari, $sesi]) {
            Jadwal::create([
                'hari_id' => $hari->id,
                'sesi_id' => $sesi->id,
                'mata_pelajaran_id' => $mapel->id,
                'guru_id' => $guru->id,
                'ruang_id' => $ruangSibuk->id,
                'siswa_id' => Siswa::factory()->create()->id,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan']));

        $response->assertOk()->assertSee('Ruang Sibuk')->assertSee('Ramai');
    }

    public function test_piutang_lama_respects_admin_selected_month_threshold(): void
    {
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['name' => 'Rangga Piutang', 'no_hp' => '+6281111111111']);
        $pembayaran = Pembayaran::create([
            'id_siswa' => $siswa->id,
            'no_hp' => $siswa->no_hp,
            'harga' => 200000,
            'status' => 0,
            'total_sudah_dibayar' => 0,
        ]);
        $pembayaran->created_at = now()->subMonths(4);
        $pembayaran->save();

        $sensitif = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan', 'piutang_bulan' => 2]));
        $sensitif->assertOk()->assertSee('Rangga Piutang')->assertSee('Rp 200.000');

        $longgar = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan', 'piutang_bulan' => 6]));
        $longgar->assertOk()->assertDontSee('Rp 200.000');
    }

    public function test_belum_ditagih_lists_students_missing_this_months_invoice(): void
    {
        $user = User::factory()->create();
        $paket = Paket::create(['nama_paket' => 'Paket Reguler', 'harga' => 150000, 'pertemuan' => 4]);
        Siswa::factory()->create(['name' => 'Dinda Belum Tagih', 'paket_pembayaran' => $paket->id]);

        $response = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan']));

        $response->assertOk()->assertSee('Dinda Belum Tagih');
    }

    public function test_data_hygiene_flags_students_without_schedule_or_phone_and_old_archive(): void
    {
        $user = User::factory()->create();
        Siswa::factory()->create(['name' => 'Farel Tanpa Jadwal']);
        Siswa::factory()->create(['name' => 'Gita Tanpa HP', 'no_hp' => null]);
        $arsip = Arsip::create(['name' => 'Halim Arsip Lama', 'kelas' => '9A', 'no_hp' => '+6280000000000']);
        $arsip->created_at = now()->subMonths(4);
        $arsip->save();

        $response = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan']));

        $response->assertOk()
            ->assertSee('Farel Tanpa Jadwal')
            ->assertSee('Gita Tanpa HP')
            ->assertSee('Halim Arsip Lama');
    }

    public function test_hidden_conflicts_from_bypassed_validation_are_detected(): void
    {
        $user = User::factory()->create();
        $hari = Hari::create(['name' => 'Senin']);
        $sesi = Sesi::factory()->create();
        $guru = Guru::factory()->create(['name' => 'Pak Bentrok']);
        $mapelA = MataPelajaran::factory()->create();
        $mapelB = MataPelajaran::factory()->create();
        $ruangA = Ruang::factory()->create();
        $ruangB = Ruang::factory()->create();

        // Simulasi hasil restore stash yang melewati ensureNoConflicts: guru sama, hari+sesi sama, kelas beda.
        Jadwal::create([
            'hari_id' => $hari->id, 'sesi_id' => $sesi->id, 'mata_pelajaran_id' => $mapelA->id,
            'guru_id' => $guru->id, 'ruang_id' => $ruangA->id, 'siswa_id' => Siswa::factory()->create()->id,
        ]);
        Jadwal::create([
            'hari_id' => $hari->id, 'sesi_id' => $sesi->id, 'mata_pelajaran_id' => $mapelB->id,
            'guru_id' => $guru->id, 'ruang_id' => $ruangB->id, 'siswa_id' => Siswa::factory()->create()->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan']));

        $response->assertOk()->assertSee('Bentrok Tersembunyi')->assertSee('Pak Bentrok');
    }

    public function test_kelas_hari_ini_shows_full_class_detail_with_students_teacher_and_subject(): void
    {
        $user = User::factory()->create();
        $hari = $this->hariHariIni();
        $sesi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);
        $mapel = MataPelajaran::factory()->create(['name' => 'Aljabar Dasar']);
        $guru = Guru::factory()->create(['name' => 'Pak Rudi']);
        $ruang = Ruang::factory()->create(['name' => 'Ruang Cendana']);
        $siswa = Siswa::factory()->create(['name' => 'Kanaya Putri', 'panggilan' => 'Kanaya', 'kelas' => '7B']);

        Jadwal::create([
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_id' => $siswa->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan']));

        $response->assertOk()
            ->assertSee('Jadwal Hari Ini')
            ->assertSee('Sesi Pagi')
            ->assertSee('Aljabar Dasar')
            ->assertSee('Pak Rudi')
            ->assertSee('Ruang Cendana')
            ->assertSee('Kanaya Putri')
            ->assertSee('7B');
    }

    public function test_periode_filter_separates_harian_and_mingguan_room_occupancy(): void
    {
        $user = User::factory()->create();
        $hariIni = $this->hariHariIni();
        $hariLain = Hari::create(['name' => 'Hari Lain']);
        $mapel = MataPelajaran::factory()->create();
        $guru = Guru::factory()->create();
        $ruangHariIni = Ruang::factory()->create(['name' => 'Ruang Hari Ini Saja']);
        $sesi = Sesi::factory()->create();

        Jadwal::create([
            'hari_id' => $hariLain->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => $mapel->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruangHariIni->id,
            'siswa_id' => Siswa::factory()->create()->id,
        ]);

        $harian = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan', 'periode' => 'harian']));
        $harian->assertOk()->assertSee('Okupansi Ruang (Hari Ini)')->assertSee('0/');

        $mingguan = $this->actingAs($user)->get(route('dashboard', ['tab' => 'ringkasan', 'periode' => 'mingguan']));
        $mingguan->assertOk()->assertSee('Okupansi Ruang (Mingguan)')->assertSee('1/');
    }

    private function hariHariIni(string $name = 'Hari Ini'): Hari
    {
        $hari = new Hari(['name' => $name]);
        $hari->id = (int) now()->isoFormat('E');
        $hari->save();

        return $hari;
    }
}
