<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Paket;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class FilterSiswaTest extends TestCase
{
    use RefreshDatabase;

    private Hari $hari;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->hari = Hari::create(['name' => 'Senin']);
    }

    private function jadwalkan(Siswa $siswa, Sesi $sesi, Guru $guru, Ruang $ruang): void
    {
        Jadwal::create([
            'hari_id' => $this->hari->id,
            'sesi_id' => $sesi->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => $guru->id,
            'ruang_id' => $ruang->id,
            'siswa_id' => $siswa->id,
            'kode_kelas' => (string) Str::uuid(),
        ]);
    }

    private function namaTerambil(array $parameter): array
    {
        Excel::fake();
        Excel::matchByRegex();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.siswa.exportExcel', $parameter))
            ->assertOk();

        $nama = [];
        Excel::assertDownloaded('/\.xlsx$/', function ($export) use (&$nama) {
            $nama = $export->collection()
                ->map(fn ($baris) => $baris['siswa']->name)
                ->unique()
                ->sort()
                ->values()
                ->all();

            return true;
        });

        return $nama;
    }

    private function paket(string $nama): Paket
    {
        return Paket::create(['nama_paket' => $nama, 'harga' => 100000, 'pertemuan' => 4]);
    }

    public function test_export_menerima_banyak_sesi_guru_dan_ruang(): void
    {
        $sesiA = Sesi::factory()->create(['name' => 'Sesi A', 'start_time' => '08:00', 'end_time' => '09:00']);
        $sesiB = Sesi::factory()->create(['name' => 'Sesi B', 'start_time' => '10:00', 'end_time' => '11:00']);
        $sesiC = Sesi::factory()->create(['name' => 'Sesi C', 'start_time' => '12:00', 'end_time' => '13:00']);

        $ana = Siswa::factory()->create(['name' => 'Ana']);
        $budi = Siswa::factory()->create(['name' => 'Budi']);
        $cici = Siswa::factory()->create(['name' => 'Cici']);

        $this->jadwalkan($ana, $sesiA, Guru::factory()->create(), Ruang::factory()->create());
        $this->jadwalkan($budi, $sesiB, Guru::factory()->create(), Ruang::factory()->create());
        $this->jadwalkan($cici, $sesiC, Guru::factory()->create(), Ruang::factory()->create());

        $this->assertSame(
            ['Ana', 'Budi'],
            $this->namaTerambil(['sesi_ids' => [$sesiA->id, $sesiB->id]])
        );
    }

    public function test_export_menyaring_guru_dan_ruang_yang_dipilih(): void
    {
        $sesi = Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00']);
        $guruDipilih = Guru::factory()->create();
        $ruangDipilih = Ruang::factory()->create();

        $ana = Siswa::factory()->create(['name' => 'Ana']);
        $budi = Siswa::factory()->create(['name' => 'Budi']);

        $this->jadwalkan($ana, $sesi, $guruDipilih, $ruangDipilih);
        $this->jadwalkan($budi, $sesi, Guru::factory()->create(), Ruang::factory()->create());

        $this->assertSame(['Ana'], $this->namaTerambil(['guru_ids' => [$guruDipilih->id]]));
        $this->assertSame(['Ana'], $this->namaTerambil(['ruang_ids' => [$ruangDipilih->id]]));
    }

    public function test_export_menerima_banyak_kelas(): void
    {
        Siswa::factory()->create(['name' => 'Ana', 'kelas' => '5']);
        Siswa::factory()->create(['name' => 'Budi', 'kelas' => '6']);
        Siswa::factory()->create(['name' => 'Cici', 'kelas' => '7']);

        $this->assertSame(['Ana', 'Cici'], $this->namaTerambil(['kelas' => ['5', '7']]));
    }

    public function test_export_paket_menemukan_paket_di_slot_mana_pun(): void
    {
        $paket = $this->paket('SD All Mapel');
        $lain = $this->paket('TK English');

        Siswa::factory()->create(['name' => 'Ana', 'paket_pembayaran' => $paket->id]);
        Siswa::factory()->create([
            'name' => 'Budi',
            'paket_pembayaran' => $lain->id,
            'paket_pembayaran_3' => $paket->id,
        ]);
        Siswa::factory()->create(['name' => 'Cici', 'paket_pembayaran' => $lain->id]);

        $this->assertSame(['Ana', 'Budi'], $this->namaTerambil(['paket_ids' => [$paket->id]]));
    }

    public function test_tautan_export_lama_dengan_koma_masih_berfungsi(): void
    {
        $sesiA = Sesi::factory()->create(['start_time' => '08:00', 'end_time' => '09:00']);
        $sesiB = Sesi::factory()->create(['start_time' => '10:00', 'end_time' => '11:00']);

        $ana = Siswa::factory()->create(['name' => 'Ana']);
        $budi = Siswa::factory()->create(['name' => 'Budi']);
        Siswa::factory()->create(['name' => 'Cici']);

        $this->jadwalkan($ana, $sesiA, Guru::factory()->create(), Ruang::factory()->create());
        $this->jadwalkan($budi, $sesiB, Guru::factory()->create(), Ruang::factory()->create());

        $this->assertSame(
            ['Ana', 'Budi'],
            $this->namaTerambil(['sesi_ids' => $sesiA->id.','.$sesiB->id])
        );
    }

    public function test_paket_id_tunggal_gaya_lama_masih_diterima(): void
    {
        $paket = $this->paket('Paket Uji');
        Siswa::factory()->create(['name' => 'Ana', 'paket_pembayaran' => $paket->id]);
        Siswa::factory()->create(['name' => 'Budi']);

        $this->assertSame(['Ana'], $this->namaTerambil(['paket_id' => $paket->id]));
    }

    public function test_panel_filter_ter_render_sebagai_checkbox_untuk_keenam_kolom(): void
    {
        Sesi::factory()->create(['name' => 'Sesi Pagi', 'start_time' => '08:00', 'end_time' => '09:00']);
        Guru::factory()->create(['name' => 'Miss Nanda']);
        Ruang::factory()->create(['name' => 'Ruang A']);
        Siswa::factory()->create(['name' => 'Ana', 'kelas' => '5']);

        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'data_siswa']));

        $halaman->assertOk();

        foreach (['opsiKelas', 'opsiPaket', 'opsiKemampuan', 'opsiSesi', 'opsiGuru', 'opsiRuang'] as $sumber) {
            $halaman->assertSee('hasil('.$sumber.')', false);
        }

        foreach (['filterKelas', 'filterPaket', 'filterKemampuan', 'filterSesis', 'filterGurus', 'filterRuangs'] as $model) {
            $halaman->assertSee('x-model="'.$model.'"', false);
        }

        $halaman->assertSee('filterMulti()', false);
        $halaman->assertSee('checkbox checkbox-primary', false);
    }

    public function test_tanpa_filter_semua_siswa_ikut_terbawa(): void
    {
        Siswa::factory()->create(['name' => 'Ana']);
        Siswa::factory()->create(['name' => 'Budi']);

        $this->assertSame(['Ana', 'Budi'], $this->namaTerambil([]));
    }
}
