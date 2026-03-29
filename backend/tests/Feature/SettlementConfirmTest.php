<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Settlement;
use App\Services\SettlementCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_replaces_rows_and_increments_version(): void
    {
        $this->seed();

        /** @var Room $room */
        $room = Room::query()->with(['members', 'items.participants'])->firstOrFail();

        Settlement::where('room_id', $room->id)->delete();
        $room->update(['settlement_version' => 0]);
        $room->refresh();

        $calculator = new SettlementCalculator;
        $calculated = $calculator->calculate($room);
        $expectedCount = count($calculated['transactions']);

        $this->assertGreaterThan(0, $expectedCount, 'テストには取引が1件以上あるシードデータが必要です。');

        $cash = [];
        foreach ($calculated['transactions'] as $transaction) {
            $payerId = $transaction['from'];
            $cash[$payerId] = ($cash[$payerId] ?? 0) + $transaction['amount'];
        }

        $this->post(route('rooms.settlement.confirm', ['room' => $room->id]), [
            'cash' => $cash,
        ]);

        $room->refresh();
        $this->assertSame(1, $room->settlement_version);
        $this->assertSame($expectedCount, Settlement::where('room_id', $room->id)->count());
        $this->assertSame(
            $expectedCount,
            Settlement::where('room_id', $room->id)->where('version', 1)->count()
        );
        $this->assertSame(
            $expectedCount,
            Settlement::where('room_id', $room->id)->where('version', 1)->where('is_paid', true)->count()
        );

        $response = $this->get(route('rooms.settlement.show', [
            'room' => $room->id,
            'query_key' => $room->password_plan,
        ]));
        $response->assertOk();
        $response->assertViewHas('isFullySettled', true);

        $this->post(route('rooms.settlement.confirm', ['room' => $room->id]), [
            'cash' => $cash,
        ]);

        $room->refresh();
        $this->assertSame(2, $room->settlement_version);
        $this->assertSame($expectedCount, Settlement::where('room_id', $room->id)->count());
        $this->assertSame(
            $expectedCount,
            Settlement::where('room_id', $room->id)->where('version', 2)->count()
        );
    }
}
