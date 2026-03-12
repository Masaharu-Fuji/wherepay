<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Map>
 */
class MapFactory extends Factory
{
    public function definition(): array
    {
        return [
            'url' => fake()->url(),
            'room_id' => Room::factory(),
        ];
    }
}
