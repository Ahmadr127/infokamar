<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomDisplayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'device_name' => 'Display Test',
            'token_hash' => hash('sha256', fake()->unique()->uuid()),
            'registered_at' => now(),
        ];
    }
}
