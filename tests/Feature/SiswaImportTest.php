<?php

namespace Tests\Feature;

use App\Exports\SiswaTemplateExport;
use App\Imports\SiswaMassalImport;
use App\Models\Paket;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class SiswaImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_template_can_be_downloaded(): void
    {
        $this->actingAs($this->admin())->get(route('admin.siswa.importTemplate'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=Kerangka-Import-Siswa.xlsx');
    }

    public function test_import_creates_a_new_student_when_the_name_does_not_exist_yet(): void
    {
        $import = new SiswaMassalImport;
        $import->collection(new Collection([
            ['nama_lengkap' => 'Fahri Ramadhan', 'panggilan' => 'Fahri', 'kelas' => '8B', 'no_hp' => '081234567890', 'nama_paket' => ''],
        ]));

        $this->assertSame(1, $import->dibuat);
        $this->assertSame(0, $import->diperbarui);
        $this->assertDatabaseHas('siswas', [
            'name' => 'Fahri Ramadhan',
            'panggilan' => 'Fahri',
            'kelas' => '8B',
            'no_hp' => '+6281234567890',
        ]);
    }

    public function test_import_updates_the_existing_student_instead_of_duplicating_by_name(): void
    {
        $siswa = Siswa::factory()->create(['name' => 'Salsa Amelia', 'kelas' => '7A']);

        $import = new SiswaMassalImport;
        $import->collection(new Collection([
            ['nama_lengkap' => 'Salsa Amelia', 'panggilan' => '', 'kelas' => '8A', 'no_hp' => '', 'nama_paket' => ''],
        ]));

        $this->assertSame(0, $import->dibuat);
        $this->assertSame(1, $import->diperbarui);
        $this->assertSame(1, Siswa::where('name', 'Salsa Amelia')->count());
        $this->assertSame('8A', $siswa->fresh()->kelas);
    }

    public function test_import_leaves_blank_fields_untouched_on_an_existing_student(): void
    {
        // Kalau baris impor cuma mau ubah kelas, kolom lain yang dikosongkan
        // di file tidak boleh ikut menghapus data yang sudah ada.
        $siswa = Siswa::factory()->create(['name' => 'Bima Saputra', 'no_hp' => '+6281111111111', 'panggilan' => 'Bima']);

        $import = new SiswaMassalImport;
        $import->collection(new Collection([
            ['nama_lengkap' => 'Bima Saputra', 'panggilan' => '', 'kelas' => '9C', 'no_hp' => '', 'nama_paket' => ''],
        ]));

        $siswa->refresh();
        $this->assertSame('9C', $siswa->kelas);
        $this->assertSame('Bima', $siswa->panggilan);
        $this->assertSame('+6281111111111', $siswa->no_hp);
    }

    public function test_import_matches_paket_by_name(): void
    {
        $paket = Paket::create(['nama_paket' => 'Paket Reguler', 'harga' => 150000, 'pertemuan' => 4]);

        $import = new SiswaMassalImport;
        $import->collection(new Collection([
            ['nama_lengkap' => 'Citra Ayu', 'panggilan' => '', 'kelas' => '', 'no_hp' => '', 'nama_paket' => 'Paket Reguler'],
        ]));

        $this->assertSame($paket->id, Siswa::where('name', 'Citra Ayu')->value('paket_pembayaran'));
    }

    public function test_import_skips_rows_with_a_blank_name(): void
    {
        $import = new SiswaMassalImport;
        $import->collection(new Collection([
            ['nama_lengkap' => '', 'panggilan' => 'Tanpa Nama', 'kelas' => '', 'no_hp' => '', 'nama_paket' => ''],
        ]));

        $this->assertSame(1, $import->dilewati);
        $this->assertSame(0, $import->dibuat);
        $this->assertDatabaseMissing('siswas', ['panggilan' => 'Tanpa Nama']);
    }

    public function test_import_route_accepts_a_real_uploaded_file_end_to_end(): void
    {
        Excel::store(new SiswaTemplateExport, 'test-import.xlsx', 'local');
        $path = storage_path('app/private/test-import.xlsx');
        if (! file_exists($path)) {
            $path = storage_path('app/test-import.xlsx');
        }

        $file = new UploadedFile($path, 'kerangka-terisi.xlsx', null, null, true);

        $this->actingAs($this->admin())->postJson(route('admin.siswa.import'), [
            'file' => $file,
        ])->assertOk()->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('siswas', ['name' => 'Contoh Siswa Satu']);

        @unlink($path);
    }
}
