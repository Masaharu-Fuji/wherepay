<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemParticipant;
use App\Models\Member;
use Illuminate\Database\Seeder;

class ItemParticipantSeeder extends Seeder
{
    public function run(): void
    {
        Item::all()->each(function (Item $item) {
            // 各アイテムに 1〜3 人の、同じ room に属する参加者を紐付け
            $roomMembers = Member::where('room_id', $item->room_id)->get();

            if ($roomMembers->isEmpty()) {
                return;
            }

            $participantsCount = min(3, $roomMembers->count());
            $participants = $roomMembers->random($participantsCount);

            foreach ($participants as $member) {
                ItemParticipant::factory()->create([
                    'item_id' => $item->id,
                    'member_id' => $member->id,
                ]);
            }
        });
    }
}
