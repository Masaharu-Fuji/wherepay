<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_limits_room_name_length(): void
    {
        $response = $this->withoutVite()->get(route('rooms.create'));

        $response->assertOk();
        $response->assertSee('maxlength="'.Room::NAME_MAX_LENGTH.'"', false);
    }

    public function test_store_accepts_room_name_at_max_length(): void
    {
        $name = str_repeat('あ', Room::NAME_MAX_LENGTH);

        $response = $this->post(route('rooms.store'), [
            'room_name' => $name,
        ]);

        $room = Room::query()->where('room_name', $name)->first();
        $this->assertNotNull($room);
        $response->assertRedirect(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));
    }

    public function test_store_rejects_room_name_over_max_length(): void
    {
        $response = $this->from(route('rooms.create'))->post(route('rooms.store'), [
            'room_name' => str_repeat('あ', Room::NAME_MAX_LENGTH + 1),
        ]);

        $response->assertRedirect(route('rooms.create'));
        $response->assertSessionHasErrors('room_name');
        $this->assertSame(0, Room::query()->count());
    }
}
