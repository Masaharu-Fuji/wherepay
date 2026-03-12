<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItemParticipant>
 */
class ItemParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'share_amount' => fake()->numberBetween(1, 5000),
            'item_id' => Item::factory(),
            'member_id' => Member::factory(),
        ];
    }
}
