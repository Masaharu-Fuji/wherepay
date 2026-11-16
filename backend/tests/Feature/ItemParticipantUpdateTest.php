<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemParticipant;
use App\Models\Member;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemParticipantUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_participants_in_the_same_room_and_recalculates_share(): void
    {
        $room = Room::factory()->create([
            'room_name' => '京都旅行',
            'password_plan' => 'roomkey1',
        ]);
        $taro = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => '太郎',
        ]);
        $hanako = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => '花子',
        ]);
        Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => '次郎',
        ]);

        $item = Item::factory()->create([
            'room_id' => $room->id,
            'item_name' => 'ランチ',
            'amount' => 4000,
            'payer_id' => $taro->id,
            'category_id' => null,
        ]);
        ItemParticipant::factory()->create([
            'item_id' => $item->id,
            'member_id' => $taro->id,
            'share_amount' => 4000,
        ]);

        $response = $this->patch(route('rooms.items.participants.update', [
            'room' => $room,
            'item' => $item,
            'query_key' => $room->password_plan,
        ]), [
            'participant_ids' => [$hanako->id, $taro->id],
        ]);

        $response->assertRedirect(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));
        $response->assertSessionHas(
            'status',
            'ランチの対象者を 花子・太郎 に直しました（1人 ¥2,000）'
        );

        $item->refresh();
        $this->assertSame('ランチ', $item->item_name);
        $this->assertSame(4000, $item->amount);
        $this->assertSame($taro->id, $item->payer_id);

        $shares = ItemParticipant::query()
            ->where('item_id', $item->id)
            ->orderBy('member_id')
            ->pluck('share_amount', 'member_id');

        $this->assertSame([
            $taro->id => 2000,
            $hanako->id => 2000,
        ], $shares->all());

        $this->followRedirects($response)->assertSee(
            'ランチの対象者を 花子・太郎 に直しました（1人 ¥2,000）'
        );
    }

    public function test_rejects_a_member_from_another_room(): void
    {
        $room = Room::factory()->create(['password_plan' => 'roomkey2']);
        $payer = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => '太郎',
        ]);
        $outsider = Member::factory()->create([
            'member_name' => '外部',
        ]);
        $item = Item::factory()->create([
            'room_id' => $room->id,
            'item_name' => 'タクシー',
            'amount' => 1800,
            'payer_id' => $payer->id,
            'category_id' => null,
        ]);
        ItemParticipant::factory()->create([
            'item_id' => $item->id,
            'member_id' => $payer->id,
            'share_amount' => 1800,
        ]);

        $response = $this->from(route('rooms.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]))->patch(route('rooms.items.participants.update', [
            'room' => $room,
            'item' => $item,
            'query_key' => $room->password_plan,
        ]), [
            'participant_ids' => [$payer->id, $outsider->id],
        ]);

        $response->assertSessionHasErrors('participant_ids.1');
        $this->assertSame(1, ItemParticipant::query()->where('item_id', $item->id)->count());
        $item->refresh();
        $this->assertSame('タクシー', $item->item_name);
        $this->assertSame(1800, $item->amount);
        $this->assertSame($payer->id, $item->payer_id);
    }
}
