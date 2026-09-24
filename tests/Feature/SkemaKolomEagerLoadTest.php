<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class SkemaKolomEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, list<string>>
     */
    private function kolomSkema(): array
    {
        $peta = [];

        foreach (Schema::getTableListing() as $tabel) {
            $tabel = str_contains($tabel, '.') ? substr($tabel, strrpos($tabel, '.') + 1) : $tabel;

            foreach (Schema::getColumnListing($tabel) as $kolom) {
                $peta[$kolom][] = $tabel;
            }
        }

        return $peta;
    }

    /**
     * @return list<array{berkas: string, daftar: string, kolom: string}>
     */
    private function kolomYangDisebut(): array
    {
        $akar = base_path();
        $hasil = [];

        foreach (['app', 'resources/views', 'database/seeders'] as $folder) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar.DIRECTORY_SEPARATOR.$folder));

            /** @var SplFileInfo $berkas */
            foreach ($iterator as $berkas) {
                if ($berkas->getExtension() !== 'php') {
                    continue;
                }

                $isi = (string) file_get_contents($berkas->getPathname());

                if (! preg_match_all('/->(?:with|load|loadMissing)\((.*?)\)/s', $isi, $panggilan)) {
                    continue;
                }

                foreach ($panggilan[1] as $argumen) {
                    if (! preg_match_all("/'([A-Za-z_][A-Za-z_0-9.]*):([a-z_0-9,]+)'/", $argumen, $cocok, PREG_SET_ORDER)) {
                        continue;
                    }

                    foreach ($cocok as $satu) {
                        foreach (explode(',', $satu[2]) as $kolom) {
                            $kolom = trim($kolom);

                            if ($kolom !== '') {
                                $hasil[] = [
                                    'berkas' => str_replace($akar.DIRECTORY_SEPARATOR, '', $berkas->getPathname()),
                                    'daftar' => $satu[1].':'.$satu[2],
                                    'kolom' => $kolom,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return $hasil;
    }

    public function test_setiap_kolom_yang_disebut_eager_load_benar_benar_ada_di_skema(): void
    {
        $skema = $this->kolomSkema();
        $disebut = $this->kolomYangDisebut();

        $this->assertNotEmpty($disebut, 'Pemindainya harus menemukan daftar kolom; kalau kosong berarti regexnya yang rusak, bukan kodenya yang bersih.');

        $hantu = [];
        foreach ($disebut as $satu) {
            if (! isset($skema[$satu['kolom']])) {
                $hantu[] = "{$satu['berkas']}  ->with('{$satu['daftar']}')  kolom '{$satu['kolom']}' tidak ada di tabel mana pun";
            }
        }

        $this->assertSame([], $hantu, implode("\n", array_merge(
            ['Ada eager-load yang menyebut kolom yang sudah tidak ada.'],
            ['SQLite memperlakukan identifier berkutip yang tidak dikenal sebagai teks biasa, jadi galat ini TIDAK akan muncul sebagai kegagalan query di tes — tapi MySQL/TiDB di produksi menolaknya dengan 500.'],
            $hantu
        )));
    }

    public function test_pemindainya_memang_bisa_mendeteksi_kolom_hantu(): void
    {
        $skema = $this->kolomSkema();

        $this->assertArrayHasKey('keterangan', $skema, 'Kolom yang jelas ada harus terbaca.');
        $this->assertArrayNotHasKey('level', $skema, 'Kolom level sudah dibuang; kalau ini gagal, pemindainya membaca skema yang salah.');
    }

    public function test_sqlite_diam_diam_menerima_kolom_hantu_yang_berkutip(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Jebakan ini khas SQLite.');
        }

        DB::table('tingkat_kemampuans')->insert(['keterangan' => 'Mahir', 'created_at' => now(), 'updated_at' => now()]);

        $ditolak = false;
        try {
            DB::select('select "id", "level", "keterangan" from "tingkat_kemampuans"');
        } catch (\Throwable $e) {
            $ditolak = true;
        }

        $this->assertFalse(
            $ditolak,
            'SQLite seharusnya MENERIMA identifier berkutip yang tidak dikenal (diperlakukan sebagai teks). '
            .'Kalau assertion ini gagal, berarti SQLite sudah berubah perilaku dan penjaga skema di atas boleh disederhanakan.'
        );
    }
}
