<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KontrolFormTampilanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_centang_hadir_di_penilaian_memakai_checkbox_daisyui(): void
    {
        $halaman = $this->actingAs(User::factory()->create())->get(route('absen.index'));

        $halaman->assertOk();
        $halaman->assertSee('x-model="anakSaatIni.hadir"', false);
        $halaman->assertSee('class="checkbox checkbox-primary"', false);
        $halaman->assertDontSee('<input type="checkbox" x-model="item.hadir">', false);
    }

    public function test_penilaian_memakai_tombol_skor_bukan_select_yang_di_x_show(): void
    {
        $halaman = $this->actingAs(User::factory()->create())->get(route('absen.index'));

        $halaman->assertOk();
        $halaman->assertDontSee('<select x-show=', false);
        $halaman->assertSee('pilihSkor(aspek.id, n)', false);
    }

    public function test_radio_filter_status_pembayaran_memakai_daisyui_dan_target_sentuh_layak(): void
    {
        $halaman = $this->actingAs(User::factory()->create())
            ->get(route('dashboard', ['tab' => 'pembayaran']));

        $halaman->assertOk();

        foreach (['radio radio-error radio-sm', 'radio radio-warning radio-sm', 'radio radio-success radio-sm'] as $kelas) {
            $halaman->assertSee($kelas, false);
        }

        $halaman->assertDontSee('focus:ring-0 w-3 h-3', false);
        $halaman->assertDontSee('dark:hover:bg-red-950/20', false);
    }

    public function test_aturan_x_cloak_ada_di_stylesheet(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('[x-cloak]', $css);
        $this->assertMatchesRegularExpression('/\[x-cloak\]\s*\{\s*display:\s*none\s*!important;/', $css);
    }

    public function test_enhancer_select_melewati_select_yang_dikendalikan_alpine(): void
    {
        $js = file_get_contents(resource_path('js/ui/searchable-select.js'));

        $this->assertStringContainsString(':not([x-show])', $js);
        $this->assertStringContainsString(':not([data-native-select="true"])', $js);
    }
}
