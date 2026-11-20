<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemParticipant;
use App\Models\Member;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_room_is_not_shown_as_settled(): void
    {
        [$room, $payer, $debtor] = $this->roomWithSharedLunch();

        $response = $this->get(route('rooms.settlement.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));

        $response->assertOk();
        $response->assertViewHas('isFullySettled', false);
        $response->assertViewHas('paidMembers', fn ($members) => $members->isEmpty());
        $response->assertViewHas('paidByMember', fn (array $paid) => $paid[$payer->id] === 3000 && $paid[$debtor->id] === 0);
        $response->assertViewHas('owedByMember', fn (array $owed) => ($owed[$debtor->id] ?? 0) === 1500 && ! array_key_exists($payer->id, $owed));
        $response->assertViewHas('receiveByMember', fn (array $receive) => ($receive[$payer->id] ?? 0) === 1500);

        $response->assertSeeText('ランチ');
        $response->assertSeeText('まさが支払い');
        $response->assertSeeText('まさ、太郎で負担（1人 ¥1,500）');
        $response->assertSeeText('太郎さんが');
        $response->assertSeeText('まささんへ払う');
        $response->assertSeeText('¥1,500');
        $response->assertSeeText('未払い');
        $response->assertDontSee('清算完了');
        $response->assertDontSee('清算は完了しています');
        $response->assertDontSee('清算が完了しました');
        $response->assertDontSee('支払える状態です');
    }

    public function test_room_with_nothing_to_transfer_is_not_called_complete(): void
    {
        $room = Room::factory()->create([
            'password_plan' => 'onlymine',
            'settlement_version' => 0,
        ]);
        $payer = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => 'まさ',
        ]);
        $item = Item::factory()->create([
            'room_id' => $room->id,
            'item_name' => '自分のコーヒー',
            'amount' => 500,
            'payer_id' => $payer->id,
            'category_id' => null,
        ]);
        ItemParticipant::factory()->create([
            'item_id' => $item->id,
            'member_id' => $payer->id,
            'share_amount' => 500,
        ]);
        $excluded = Item::factory()->create([
            'room_id' => $room->id,
            'item_name' => '対象なしの菓子',
            'amount' => 800,
            'payer_id' => $payer->id,
            'category_id' => null,
        ]);

        $response = $this->get(route('rooms.settlement.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]));

        $response->assertOk();
        $response->assertViewHas('isFullySettled', false);
        $response->assertViewHas('excludedItemCount', 1);
        $response->assertSeeText('誰かが誰かに払う金額はありません');
        $response->assertSeeText('払う人がいないので、おつり計算はありません');
        $response->assertSeeText('対象者のいない品目が1件あります');
        $response->assertSeeText('対象なしの菓子');
        $response->assertSeeText('対象者がいないので、この品目は上の金額に入っていません');
        $response->assertDontSee('清算完了');
        $response->assertDontSee('清算は完了しています');

        $this->assertSame($excluded->item_name, '対象なしの菓子');
    }

    public function test_debtor_becomes_able_to_pay_only_after_enough_cash(): void
    {
        [$room, $payer, $debtor] = $this->roomWithSharedLunch();

        $response = $this->post(route('rooms.settlement.show', [
            'room' => $room,
            'query_key' => $room->password_plan,
        ]), [
            'cash' => [
                $debtor->id => '1500',
            ],
        ]);

        $response->assertOk();
        $response->assertViewHas('isFullySettled', true);
        $response->assertViewHas('paidMembers', function ($members) use ($payer, $debtor) {
            $ids = $members->pluck('id')->all();

            return $ids === [$debtor->id] && ! in_array($payer->id, $ids, true);
        });
        $response->assertSeeText('清算が完了しました');
        $response->assertSeeText('支払える人');
        $response->assertSeeText('支払える状態です');
        $response->assertDontSee('未払い');
    }

    /**
     * @return array{0: Room, 1: Member, 2: Member}
     */
    private function roomWithSharedLunch(): array
    {
        $room = Room::factory()->create([
            'password_plan' => 'cb6bf877',
            'settlement_version' => 0,
        ]);
        $payer = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => 'まさ',
        ]);
        $debtor = Member::factory()->create([
            'room_id' => $room->id,
            'member_name' => '太郎',
        ]);
        $item = Item::factory()->create([
            'room_id' => $room->id,
            'item_name' => 'ランチ',
            'amount' => 3000,
            'payer_id' => $payer->id,
            'category_id' => null,
        ]);
        foreach ([$payer, $debtor] as $member) {
            ItemParticipant::factory()->create([
                'item_id' => $item->id,
                'member_id' => $member->id,
                'share_amount' => 1500,
            ]);
        }

        return [$room, $payer, $debtor];
    }
}
