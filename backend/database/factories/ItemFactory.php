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
        $itemNames = [
            'ランチ代',
            'ディナー代',
            'カフェ代',
            '飲み代',
            'コンビニ',
            'スーパー',
            'タクシー代',
            '電車代',
            'バス代',
            'ガソリン代',
            'ホテル代',
            '入場料',
            'お土産代',
            '日用品',
            '雑費',
        ];

        return [
            'item_name' => fake()->randomElement($itemNames),
            'memo' => fake()->optional()->sentence(),
            'amount' => fake()->numberBetween(100, 10000),
            'paid_at' => fake()->date(),
            'category_id' => fake()->optional()->randomElement([null, ItemCategory::factory()]),
            'payer_id' => Member::factory(),
        ];
    }
}
