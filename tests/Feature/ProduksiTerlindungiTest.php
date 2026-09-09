<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Alur kerja project ini menukar .env bolak-balik antara database lokal dan
 * produksi. Satu kali salah tukar lalu menjalankan migrate:fresh berarti
 * seluruh pembukuan hilang. Tes ini mengunci penjaga yang mencegahnya.
 */
class ProduksiTerlindungiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Dimatikan lagi agar tes lain yang memakai RefreshDatabase tidak ikut
        // terblokir oleh penjaga yang dinyalakan di sini.
        DB::prohibitDestructiveCommands(false);

        parent::tearDown();
    }

    public function test_destructive_commands_are_blocked_when_running_as_production(): void
    {
        DB::prohibitDestructiveCommands(true);

        foreach (['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'] as $perintah) {
            // --force pun tidak boleh menembus penjaga ini: perintah berhenti
            // dengan exit code gagal, bukan menjalankan penghapusan.
            $this->artisan($perintah, ['--force' => true])->assertFailed();
        }

        // Tabel harus tetap utuh setelah keempat perintah di atas ditolak.
        $this->assertTrue(Schema::hasTable('pembayarans'));
        $this->assertTrue(Schema::hasTable('siswas'));
    }

    public function test_guard_is_wired_into_the_application_bootstrap(): void
    {
        // Memastikan penjaganya benar-benar dipasang di AppServiceProvider,
        // bukan sekadar tersedia di framework.
        $sumber = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString('prohibitDestructiveCommands', $sumber);
        $this->assertStringContainsString('isProduction()', $sumber);
    }
}
