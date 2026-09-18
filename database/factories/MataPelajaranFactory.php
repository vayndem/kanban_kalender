<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MataPelajaranFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Biologi', 'Fisika', 'Kimia', 'Ekonomi']),
        ];
    }
}
