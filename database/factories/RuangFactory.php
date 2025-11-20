<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;


class RuangFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Ruang ' . $this->faker->numberBetween(101, 305), // Misal: "Ruang 101"
        ];
    }
}
