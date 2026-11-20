<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Member;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemMemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_memo_of_255_characters(): void
    {
        [$room, $member] = $this->roomWithMember();
        $memo = str_repeat('あ', 255);

        $response = $this->post(route('rooms.items.store', ['room' => $room]), [
            'item_name' => 'ランチ',
            'amount' => 1200,
            'payer_id' => $member->id,
            'participant_ids' => [$member->id],
            'memo' => $memo,
        ]);

        $response->assertRedirect(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));

        $item = Item::query()->where('room_id', $room->id)->first();
        $this->assertNotNull($item);
        $this->assertSame($memo, $item->memo);
    }

    public function test_rejects_a_memo_longer_than_255_characters(): void
    {
        [$room, $member] = $this->roomWithMember();

        $response = $this->from(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]))->post(route('rooms.items.store', ['room' => $room]), [
            'item_name' => 'ランチ',
            'amount' => 1200,
            'payer_id' => $member->id,
            'participant_ids' => [$member->id],
            'memo' => str_repeat('あ', 256),
        ]);

        $response->assertRedirect(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));
        $response->assertSessionHasErrors('memo');
        $this->assertSame(0, Item::query()->count());
    }

    public function test_stores_an_item_without_a_memo(): void
    {
        [$room, $member] = $this->roomWithMember();

        $response = $this->post(route('rooms.items.store', ['room' => $room]), [
            'item_name' => 'ランチ',
            'amount' => 1200,
            'payer_id' => $member->id,
            'participant_ids' => [$member->id],
        ]);

        $response->assertRedirect(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));

        $item = Item::query()->where('room_id', $room->id)->first();
        $this->assertNotNull($item);
        $this->assertNull($item->memo);
    }

    /**
     * @return array{0: Room, 1: Member}
     */
    private function roomWithMember(): array
    {
        $room = Room::factory()->create([
            'room_name' => '京都旅行',
            'password_plan' => 'roomkey1',
        ]);
        $member = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => '太郎',
        ]);

        return [$room, $member];
    }
}
