<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Pengembangan project ini menukar .env bolak-balik antara database
        // lokal dan produksi. Satu kali salah tukar lalu menjalankan
        // migrate:fresh / migrate:refresh / migrate:reset / db:wipe berarti
        // seluruh pembukuan produksi terhapus. Penjaga ini memblokir perintah
        // perusak itu selama APP_ENV=production, dan --force tidak bisa
        // menembusnya.
        DB::prohibitDestructiveCommands($this->app->isProduction());

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        config([
            'excel.temporary_files.local_path' => rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'laravel-excel',
        ]);
    }
}
