<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Services\SettlementCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_basic_settlement_for_room(): void
    {
        $this->seed();

        /** @var Room $room */
        $room = Room::query()->with(['members', 'items.participants'])->firstOrFail();

        $calculator = new SettlementCalculator;
        $result = $calculator->calculate($room);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('memberDebts', $result);
        $this->assertArrayHasKey('balances', $result);
        $this->assertArrayHasKey('transactions', $result);

        $this->assertIsInt($result['total']);
        $this->assertIsArray($result['memberDebts']);
        $this->assertIsArray($result['balances']);
        $this->assertIsArray($result['transactions']);

        $this->assertEqualsWithDelta(
            0.0,
            array_reduce($result['balances'], static fn (float $carry, float $balance): float => $carry + $balance, 0.0),
            1.0e-6,
            'balances の合計は 0 になる必要があります。'
        );
    }

    public function test_calculate_change_with_valid_and_invalid_inputs(): void
    {
        $calculator = new SettlementCalculator;

        $transactions = [
            ['from' => 1, 'to' => 2, 'amount' => 1000],
            ['from' => 1, 'to' => 3, 'amount' => 500],
        ];

        $cashInputs = [
            1 => '2000',   // 十分な現金
            2 => '',       // 未入力
            3 => 'abc',    // 数値でない
        ];

        $change = $calculator->calculateChange($transactions, $cashInputs);

        $this->assertSame(500.0, $change[1]);
        $this->assertNull($change[2]);
        $this->assertNull($change[3]);
    }
}
