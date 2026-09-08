<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\RingkasanService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesiWaktuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_jam_sesi_dibaca_sebagai_teks_jam_bukan_tanggal(): void
    {
        $sesi = Sesi::factory()->create(['start_time' => '09:00', 'end_time' => '10:30']);

        $segar = Sesi::find($sesi->id);

        $this->assertSame('09:00', $segar->start_time);
        $this->assertSame('10:30', $segar->end_time);
        $this->assertStringNotContainsString('-', (string) $segar->start_time);
    }

    public function test_potongan_lima_karakter_pertama_tetap_jam(): void
    {
        $sesi = Sesi::factory()->create(['start_time' => '13:45', 'end_time' => '15:00']);

        $this->assertSame('13:45', substr((string) $sesi->fresh()->start_time, 0, 5));
    }

    public function test_label_sesi_menempel_dengan_rentang_jamnya(): void
    {
        $sesi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);

        $this->assertSame('08:00–09:00', $sesi->fresh()->rentang_jam);
        $this->assertSame('Sesi Pagi - 08:00–09:00', $sesi->fresh()->label);
    }

    public function test_jadwal_hari_ini_di_ringkasan_urut_menurut_jam_sesi(): void
    {
        $hari = new Hari(['name' => 'Hari Ini']);
        $hari->id = (int) now()->isoFormat('E');
        $hari->save();

        $siang = Sesi::factory()->create(['name' => 'Sesi Siang', 'start_time' => '13:00', 'end_time' => '14:00']);
        $pagi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);

        $guru = Guru::factory()->create();
        $ruang = Ruang::factory()->create();

        foreach ([$siang, $pagi] as $sesi) {
            Jadwal::create([
                'hari_id' => $hari->id,
                'sesi_id' => $sesi->id,
                'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
                'guru_id' => $guru->id,
                'ruang_id' => $ruang->id,
                'siswa_id' => Siswa::factory()->create()->id,
            ]);
        }

        $kartu = app(RingkasanService::class)->kelasHariIni();

        $this->assertSame('08:00', $kartu[0]['sesi_start'], 'Sesi paling pagi harus tampil lebih dulu.');
        $this->assertSame('Sesi Pagi - 08:00–09:00', $kartu[0]['sesi_label']);
        $this->assertSame('13:00', $kartu[1]['sesi_start']);
    }

    public function test_durasi_beban_mengajar_tidak_pernah_negatif(): void
    {
        $hari = Hari::create(['name' => 'Senin']);
        $sesi = Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:30']);
        $guru = Guru::factory()->create(['name' => 'Bu Sinta']);

        Jadwal::create([
            'hari_id' => $hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'siswa_id' => Siswa::factory()->create()->id,
        ]);

        $beban = app(RingkasanService::class)->bebanGuru('mingguan');
        $baris = collect($beban['beban'])->firstWhere('nama', 'Bu Sinta');

        $this->assertSame(90, $baris['total_menit'], 'Durasi sesi 08:00-09:30 harus 90 menit, bukan negatif.');
    }

    public function test_halaman_jadwal_menampilkan_jam_sesi_yang_terbaca(): void
    {
        $sesi = Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);
        Hari::create(['name' => 'Senin']);

        $respon = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'jadwal']));

        $respon->assertOk()->assertSee('08:00')->assertDontSee((string) now()->year.'-'.$sesi->id);
    }
}
