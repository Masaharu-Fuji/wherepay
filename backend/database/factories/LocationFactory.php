<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            // 東京都新宿区付近の緯度・経度レンジに制限
            // 新宿駅周辺: おおよそ 35.68〜35.71, 139.68〜139.72
            'latitude' => fake()->randomFloat(6, 35.68, 35.71),
            'longitude' => fake()->randomFloat(6, 139.68, 139.72),
            'url_map' => fake()->optional()->url(),
            'item_id' => Item::factory(),
        ];
    }
}
