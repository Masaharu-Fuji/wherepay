<?php

namespace Database\Seeders;

use App\Models\Map;
use App\Models\Room;
use Illuminate\Database\Seeder;

class MapSeeder extends Seeder
{
    public function run(): void
    {
        Room::all()->each(function (Room $room) {
            Map::factory()->create([
                'room_id' => $room->id,
            ]);
        });
    }
}
