<?php

namespace Tests\Feature;

use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaMassalImport;
use App\Models\Paket;
use App\Models\Siswa;
use App\Models\TingkatKemampuan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class PaketDanImportSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function paket(string $nama, int $pertemuan = 4): Paket
    {
        return Paket::create(['nama_paket' => $nama, 'harga' => 200000, 'pertemuan' => $pertemuan]);
    }

    private function impor(array $baris): SiswaMassalImport
    {
        $import = new SiswaMassalImport;
        $import->collection(new Collection($baris));

        return $import;
    }

    public function test_workshop_menyimpan_lima_slot_paket(): void
    {
        $paket = collect(range(1, 5))->map(fn ($i) => $this->paket('Paket '.$i))->all();

        $respon = $this->actingAs(User::factory()->create())->postJson(route('admin.siswa.store'), [
            'name' => 'Ana',
            'kelas' => '5',
            'paket_pembayaran' => $paket[0]->id,
            'paket_pembayaran_2' => $paket[1]->id,
            'paket_pembayaran_3' => $paket[2]->id,
            'paket_pembayaran_4' => $paket[3]->id,
            'paket_pembayaran_5' => $paket[4]->id,
        ]);

        $respon->assertOk();

        $siswa = Siswa::where('name', 'Ana')->firstOrFail();
        foreach ($paket as $i => $p) {
            $kolom = $i === 0 ? 'paket_pembayaran' : 'paket_pembayaran_'.($i + 1);
            $this->assertSame($p->id, $siswa->$kolom, "Slot $kolom tidak tersimpan.");
        }
    }

    public function test_slot_paket_yang_tidak_ada_ditolak(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.siswa.store'), [
                'name' => 'Budi',
                'paket_pembayaran_3' => 999999,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('siswas', ['name' => 'Budi']);
    }

    public function test_ubah_siswa_bisa_mengosongkan_slot_paket(): void
    {
        $paket = $this->paket('Paket A');
        $siswa = Siswa::factory()->create([
            'name' => 'Cici',
            'paket_pembayaran' => $paket->id,
            'paket_pembayaran_2' => $paket->id,
        ]);

        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.siswa.update', $siswa->id), [
                'name' => 'Cici',
                'paket_pembayaran' => $paket->id,
                'paket_pembayaran_2' => null,
            ])
            ->assertOk();

        $siswa->refresh();
        $this->assertSame($paket->id, $siswa->paket_pembayaran);
        $this->assertNull($siswa->paket_pembayaran_2);
    }

    public function test_import_mengisi_kemampuan_dari_angka_level(): void
    {
        $level2 = TingkatKemampuan::create(['level' => 2, 'keterangan' => 'Menengah']);
        TingkatKemampuan::create(['level' => 3, 'keterangan' => 'Mahir']);

        $this->impor([['nama_lengkap' => 'Ana', 'kemampuan' => '2']]);

        $this->assertSame($level2->id, Siswa::where('name', 'Ana')->value('tingkat_kemampuan_id'));
    }

    public function test_import_melewati_level_kemampuan_yang_belum_terdaftar(): void
    {
        TingkatKemampuan::create(['level' => 1, 'keterangan' => 'Dasar']);

        $hasil = $this->impor([['nama_lengkap' => 'Budi', 'kemampuan' => '9']]);

        $this->assertSame(1, $hasil->dibuat, 'Barisnya tetap masuk walau levelnya tidak dikenal.');
        $this->assertNull(Siswa::where('name', 'Budi')->value('tingkat_kemampuan_id'));
    }

    public function test_import_mengisi_paket_kedua_sampai_kelima(): void
    {
        $a = $this->paket('Paket A');
        $b = $this->paket('Paket B');
        $c = $this->paket('Paket C');

        $this->impor([[
            'nama_lengkap' => 'Cici',
            'nama_paket' => 'Paket A',
            'nama_paket_2' => 'Paket B',
            'nama_paket_4' => 'Paket C',
        ]]);

        $siswa = Siswa::where('name', 'Cici')->firstOrFail();
        $this->assertSame($a->id, $siswa->paket_pembayaran);
        $this->assertSame($b->id, $siswa->paket_pembayaran_2);
        $this->assertNull($siswa->paket_pembayaran_3);
        $this->assertSame($c->id, $siswa->paket_pembayaran_4);
    }

    public function test_kolom_kosong_tidak_menimpa_data_lama(): void
    {
        $paket = $this->paket('Paket A');
        $level = TingkatKemampuan::create(['level' => 1, 'keterangan' => 'Dasar']);

        Siswa::factory()->create([
            'name' => 'Dedi',
            'kelas' => '6',
            'paket_pembayaran' => $paket->id,
            'tingkat_kemampuan_id' => $level->id,
        ]);

        $hasil = $this->impor([['nama_lengkap' => 'Dedi', 'panggilan' => 'Ded']]);

        $siswa = Siswa::where('name', 'Dedi')->firstOrFail();
        $this->assertSame(1, $hasil->diperbarui);
        $this->assertSame('Ded', $siswa->panggilan);
        $this->assertSame('6', $siswa->kelas);
        $this->assertSame($paket->id, $siswa->paket_pembayaran);
        $this->assertSame($level->id, $siswa->tingkat_kemampuan_id);
    }

    public function test_kerangka_import_memuat_kolom_kemampuan_dan_lima_paket(): void
    {
        $judul = (new SiswaTemplateExport)->headings();

        $this->assertContains('Kemampuan', $judul);
        foreach (['Nama Paket', 'Nama Paket 2', 'Nama Paket 3', 'Nama Paket 4', 'Nama Paket 5'] as $kolom) {
            $this->assertContains($kolom, $judul);
        }
    }
}
