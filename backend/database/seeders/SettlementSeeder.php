<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Settlement;
use Illuminate\Database\Seeder;

class SettlementSeeder extends Seeder
{
    public function run(): void
    {
        Room::with('members')->each(function (Room $room) {
            $members = $room->members;

            if ($members->count() < 2) {
                return;
            }

            // 各ルーム内のメンバー同士で精算データを作成
            for ($i = 0; $i < 5; $i++) {
                [$payer, $receiver] = $members->random(2)->all();

                Settlement::factory()->create([
                    'room_id' => $room->id,
                    'payer_id' => $payer->id,
                    'receiver_id' => $receiver->id,
                ]);
            }
        });
    }
}
