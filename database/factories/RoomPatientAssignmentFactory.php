<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomPatientAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'patient_name' => fake()->name(),
            'care_class' => 'VIP',
            'started_at' => now(),
        ];
    }
}
