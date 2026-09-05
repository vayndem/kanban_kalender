<?php

namespace Tests\Feature;

use App\Exports\JadwalExport;
use App\Exports\PembayaranExport;
use App\Exports\SiswaExport;
use App\Exports\SiswaTemplateExport;
use App\Models\Guru;
use App\Models\Hari;
use App\Models\Jadwal;
use App\Models\MataPelajaran;
use App\Models\Pembayaran;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * Excel/PhpSpreadsheet menebak tipe sel dari isinya -- tanpa penanda tegas,
 * "+6281234567890" dibaca sebagai angka, tanda "+" hilang, dan nomor panjang
 * berubah jadi notasi ilmiah (mis. 6,2851E+12). Tes ini mengunci bahwa setiap
 * kolom no_hp di semua file export tersimpan sebagai teks apa adanya.
 */
class ExportPhoneNumberFormatTest extends TestCase
{
    use RefreshDatabase;

    private function bacaSelPertama(object $export, string $namaFile, string $kolom, int $baris): array
    {
        Excel::store($export, $namaFile, 'local');
        $path = storage_path('app/private/'.$namaFile);
        if (! file_exists($path)) {
            $path = storage_path('app/'.$namaFile);
        }

        $spreadsheet = IOFactory::load($path);
        $cell = $spreadsheet->getActiveSheet()->getCell($kolom.$baris);
        $hasil = ['value' => $cell->getValue(), 'type' => $cell->getDataType()];

        @unlink($path);

        return $hasil;
    }

    public function test_siswa_export_keeps_phone_number_as_text(): void
    {
        Siswa::factory()->create(['name' => 'Contoh Siswa', 'no_hp' => '+6281234567890']);

        $sel = $this->bacaSelPertama(new SiswaExport(Siswa::with('jadwals')->get(), 'Semua Siswa'), 'siswa-export-test.xlsx', 'D', 5);

        $this->assertSame('+6281234567890', $sel['value']);
        $this->assertSame(DataType::TYPE_STRING, $sel['type']);
    }

    public function test_siswa_template_keeps_example_phone_number_as_text(): void
    {
        $sel = $this->bacaSelPertama(new SiswaTemplateExport, 'siswa-template-test.xlsx', 'D', 2);

        $this->assertSame('081234567890', $sel['value']);
        $this->assertSame(DataType::TYPE_STRING, $sel['type']);
    }

    public function test_pembayaran_export_ringkasan_keluarga_keeps_phone_number_as_text(): void
    {
        $siswa = Siswa::factory()->create(['no_hp' => '+6281234567890']);
        Pembayaran::create([
            'id_siswa' => $siswa->id, 'no_hp' => $siswa->no_hp,
            'harga' => 100000, 'status' => 0, 'total_sudah_dibayar' => 0,
        ]);

        $export = new PembayaranExport(Pembayaran::with('siswa')->get(), collect(), []);
        $sel = $this->bacaSelPertama($export, 'pembayaran-export-test.xlsx', 'A', 5);

        $this->assertSame('+6281234567890', $sel['value']);
        $this->assertSame(DataType::TYPE_STRING, $sel['type']);
    }

    public function test_jadwal_export_keeps_student_phone_number_as_text(): void
    {
        $siswa = Siswa::factory()->create(['no_hp' => '+6281234567890']);
        Jadwal::create([
            'siswa_id' => $siswa->id,
            'mata_pelajaran_id' => MataPelajaran::factory()->create()->id,
            'guru_id' => Guru::factory()->create()->id,
            'hari_id' => Hari::factory()->create(['name' => 'Senin'.uniqid()])->id,
            'ruang_id' => Ruang::factory()->create()->id,
            'sesi_id' => Sesi::factory()->create()->id,
        ]);

        $export = new JadwalExport(Jadwal::with(['siswa', 'hari', 'sesi', 'mataPelajaran', 'guru', 'ruang'])->get());
        $sel = $this->bacaSelPertama($export, 'jadwal-export-test.xlsx', 'K', 2);

        $this->assertSame('+6281234567890', $sel['value']);
        $this->assertSame(DataType::TYPE_STRING, $sel['type']);
    }
}
