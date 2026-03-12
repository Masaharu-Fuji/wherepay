<?php

namespace Database\Factories;

use App\Models\ItemCategory;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_name' => fake()->words(2, true),
            'memo' => fake()->optional()->sentence(),
            'amount' => fake()->numberBetween(100, 10000),
            'paid_at' => fake()->date(),
            'category_id' => fake()->optional()->randomElement([null, ItemCategory::factory()]),
            'payer_id' => Member::factory(),
        ];
    }
}
