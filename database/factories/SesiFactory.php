<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SesiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sesi ' . $this->faker->randomDigitNotNull(),
            'start_time' => $this->faker->time('H:i:s'),
            'end_time' => $this->faker->time('H:i:s'),
        ];
    }
}
