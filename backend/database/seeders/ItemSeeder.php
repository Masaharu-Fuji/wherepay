<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Member;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        // 既存メンバーごとにアイテムを作成
        Member::all()->each(function (Member $member) {
            Item::factory()
                ->count(5)
                ->create([
                    'room_id' => $member->room_id,
                    'payer_id' => $member->id,
                ]);
        });
    }
}
