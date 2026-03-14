<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = Room::factory()
            ->count(3)
            ->create();

        foreach ($rooms as $room) {
            Log::info('Room created by seeder', [
                'room_id' => $room->id,
                'room_name' => $room->room_name,
                'password_plan' => $room->password_plan,
            ]);
            $this->command->info("Room #{$room->id} ({$room->room_name}): password_plan={$room->password_plan}");
        }
    }
}
