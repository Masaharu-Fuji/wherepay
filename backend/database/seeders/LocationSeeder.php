<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // 各アイテムに 0〜2 件のロケーションを紐付け
        Item::all()->each(function (Item $item) {
            $count = rand(0, 2);

            if ($count === 0) {
                return;
            }

            Location::factory()
                ->count($count)
                ->create([
                    'item_id' => $item->id,
                ]);
        });
    }
}
