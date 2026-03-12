<?php

namespace App\Services;

use App\Models\Room;
use Illuminate\Support\Collection;

class SettlementCalculator
{
    /**
     * @return array{
     *     total:int,
     *     memberDebts: array<int,float>,
     *     balances: array<int,float>,
     *     transactions: array<int,array{from:int,to:int,amount:int}>
     * }
     */
    public function calculate(Room $room): array
    {
        $room->loadMissing([
            'members',
            'members.itemParticipants',
            'items.payer',
            'items.participants',
            'items.location',
        ]);

        /** @var Collection<int,\App\Models\Member> $members */
        $members = $room->members;
        $items = $room->items;

        $total = (int) $items->sum('amount');

        $balances = [];
        $memberDebts = [];

        foreach ($members as $member) {
            $balances[$member->id] = 0.0;
            $memberDebts[$member->id] = 0;
        }

        foreach ($items as $item) {
            $participants = $item->participants;

            if ($participants->isEmpty()) {
                continue;
            }

            $perPerson = $item->amount / max(1, $participants->count());

            // Seeder 等の影響で、同一ルーム外のメンバー ID が紐づくケースに備えてガードする
            if (! array_key_exists($item->payer_id, $balances)) {
                $balances[$item->payer_id] = 0.0;
                $memberDebts[$item->payer_id] = 0;
            }

            $balances[$item->payer_id] += $item->amount;

            foreach ($participants as $participant) {
                if (! array_key_exists($participant->member_id, $balances)) {
                    $balances[$participant->member_id] = 0.0;
                    $memberDebts[$participant->member_id] = 0;
                }

                $balances[$participant->member_id] -= $perPerson;
                $memberDebts[$participant->member_id] += $perPerson;
            }
        }

        $transactions = $this->buildTransactions($balances);

        return [
            'total' => $total,
            'memberDebts' => array_map(
                static fn (float $value): float => round($value),
                $memberDebts
            ),
            'balances' => $balances,
            'transactions' => $transactions,
        ];
    }

    /**
     * @param  array<int,float>  $balances
     * @return array<int,array{from:int,to:int,amount:int}>
     */
    private function buildTransactions(array $balances): array
    {
        $debtors = [];
        $creditors = [];

        foreach ($balances as $memberId => $balance) {
            if ($balance < -0.01) {
                $debtors[] = ['id' => $memberId, 'amount' => -$balance];
            } elseif ($balance > 0.01) {
                $creditors[] = ['id' => $memberId, 'amount' => $balance];
            }
        }

        usort($debtors, fn ($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        $transactions = [];
        $i = 0;
        $j = 0;

        while ($i < count($debtors) && $j < count($creditors)) {
            $debtor = &$debtors[$i];
            $creditor = &$creditors[$j];

            $amount = min($debtor['amount'], $creditor['amount']);

            $transactions[] = [
                'from' => $debtor['id'],
                'to' => $creditor['id'],
                'amount' => (int) round($amount),
            ];

            $debtor['amount'] -= $amount;
            $creditor['amount'] -= $amount;

            if ($debtor['amount'] < 0.01) {
                $i++;
            }

            if ($creditor['amount'] < 0.01) {
                $j++;
            }
        }

        return $transactions;
    }

    /**
     * @param  array<int,array{from:int,to:int,amount:int}>  $transactions
     * @param  array<int,string>  $cashInputs
     * @return array<int,float|null>
     */
    public function calculateChange(array $transactions, array $cashInputs): array
    {
        $result = [];

        foreach ($cashInputs as $memberId => $value) {
            $value = trim((string) $value);

            if ($value === '') {
                $result[$memberId] = null;

                continue;
            }

            if (! is_numeric($value)) {
                $result[$memberId] = null;

                continue;
            }

            $cash = (float) $value;

            $totalOwed = 0.0;

            foreach ($transactions as $transaction) {
                if ($transaction['from'] === (int) $memberId) {
                    $totalOwed += $transaction['amount'];
                }
            }

            $result[$memberId] = $cash - $totalOwed;
        }

        return $result;
    }
}
