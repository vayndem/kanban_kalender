<?php

namespace Database\Factories;

use App\Models\PembayaranDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembayaranDetail>
 */
class PembayaranDetailFactory extends Factory
{
    protected $model = PembayaranDetail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pembayaran' => fake()->numberBetween(50_000, 500_000),
            'keterangan' => 'Cicilan',
        ];
    }
}
