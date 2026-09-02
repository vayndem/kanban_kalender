<?php

namespace Database\Factories;

use App\Models\Guru;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            Role::findOrCreate(User::ROLE_ADMIN, 'web');
            $user->syncRoles([User::ROLE_ADMIN]);
        });
    }

    public function guru(?Guru $guru = null): static
    {
        return $this->state(fn () => ['guru_id' => $guru?->id])
            ->afterCreating(function (User $user) {
                Role::findOrCreate(User::ROLE_GURU, 'web');
                $user->syncRoles([User::ROLE_GURU]);
            });
    }

    public function tanpaPeran(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles([]));
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
