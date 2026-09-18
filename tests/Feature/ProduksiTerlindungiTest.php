<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProduksiTerlindungiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        DB::prohibitDestructiveCommands(false);

        parent::tearDown();
    }

    public function test_destructive_commands_are_blocked_when_running_as_production(): void
    {
        DB::prohibitDestructiveCommands(true);

        foreach (['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'] as $perintah) {
            $this->artisan($perintah, ['--force' => true])->assertFailed();
        }

        $this->assertTrue(Schema::hasTable('pembayarans'));
        $this->assertTrue(Schema::hasTable('siswas'));
    }

    public function test_guard_is_wired_into_the_application_bootstrap(): void
    {
        $sumber = file_get_contents(app_path('Providers/AppServiceProvider.php'));

        $this->assertStringContainsString('prohibitDestructiveCommands', $sumber);
        $this->assertStringContainsString('isProduction()', $sumber);
    }
}
