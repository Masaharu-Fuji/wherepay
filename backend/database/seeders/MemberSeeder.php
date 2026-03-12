<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Room;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // 既存の Room に対してメンバーを作成
        Room::all()->each(function (Room $room) {
            Member::factory()
                ->count(4)
                ->create([
                    'room_id' => $room->id,
                ]);
        });
    }
}
