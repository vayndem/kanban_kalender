<?php

namespace Database\Factories;

use App\Models\Diskon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Diskon>
 */
class DiskonFactory extends Factory
{
    protected $model = Diskon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_hp' => '+62812'.fake()->unique()->numerify('########'),
            'diskon' => fake()->numberBetween(5_000, 50_000),
            'keterangan' => 'Potongan Diskon Keluarga',
        ];
    }

    public function massal(): static
    {
        return $this->state(fn () => [
            'no_hp' => null,
            'keterangan' => 'Diskon Massal',
        ]);
    }
}
