<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('R-???')),
            'name' => 'Ruang '.fake()->word(),
            'care_class' => fake()->randomElement(['VIP', 'I', 'II', 'III']),
            'is_active' => true,
        ];
    }
}
