<?php

namespace Database\Seeders;

use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            '食費',
            '交通費',
            '宿泊費',
            'レジャー',
            '雑費',
        ];

        foreach ($categories as $name) {
            ItemCategory::factory()->create([
                'category_name' => $name,
            ]);
        }
    }
}
