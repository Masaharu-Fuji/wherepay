<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Settlement>
 */
class SettlementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'payer_id' => Member::factory(),
            'receiver_id' => Member::factory(),
            'amount' => fake()->numberBetween(100, 10000),
            'is_paid' => fake()->boolean(30),
        ];
    }
}
