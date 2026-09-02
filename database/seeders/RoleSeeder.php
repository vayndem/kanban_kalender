<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([User::ROLE_ADMIN, User::ROLE_GURU] as $peran) {
            Role::findOrCreate($peran, 'web');
        }

        $tanpaPeran = User::doesntHave('roles')->get();

        foreach ($tanpaPeran as $user) {
            $user->assignRole($user->guru_id ? User::ROLE_GURU : User::ROLE_ADMIN);
        }

        if ($tanpaPeran->isNotEmpty()) {
            $this->command?->info("Peran diberikan ke {$tanpaPeran->count()} akun yang sebelumnya belum berperan.");
        }
    }
}
