<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\Room;
use App\Models\Settlement;
use App\Services\SettlementCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettlementController extends Controller
{
    public function show(Request $request, Room $room, SettlementCalculator $calculator): View
    {
        $result = $calculator->calculate($room);

        $cashInputs = $request->input('cash', []);

        $changeByMember = $calculator->calculateChange(
            $result['transactions'],
            $cashInputs,
        );

        // おつりが 0 以上なら、その人の「支払うべきお金」は完了とみなして is_paid を更新（payer 側で判定）
        foreach ($changeByMember as $memberId => $change) {
            if ($change !== null && $change >= 0) {
                Settlement::where('room_id', $room->id)
                    ->where('payer_id', $memberId)
                    ->update(['is_paid' => true]);
            }
        }

        // 各メンバーが実際に支払うべき総額（transactions ベース）
        $owedByMember = [];

        foreach ($result['transactions'] as $transaction) {
            $fromId = $transaction['from'];

            if (! array_key_exists($fromId, $owedByMember)) {
                $owedByMember[$fromId] = 0;
            }

            $owedByMember[$fromId] += $transaction['amount'];
        }

        // ルーム全体が完了しているかどうか
        // 「支払うべき金額が 0 のメンバー」は最初から完了扱いとし、
        // 実際に支払い義務のあるメンバーのみを判定対象とする
        $isFullySettled = true;

        foreach ($room->members as $member) {
            $memberId = $member->id;
            $owed = $owedByMember[$memberId] ?? 0;

            // 支払義務がない人は常に OK
            if ($owed === 0) {
                continue;
            }

            // 支払義務がある人については、おつりが null でなく 0 以上なら完了
            if (
                ! array_key_exists($memberId, $changeByMember)
                || $changeByMember[$memberId] === null
                || $changeByMember[$memberId] < 0
            ) {
                $isFullySettled = false;

                break;
            }
        }

        $room->loadMissing([
            'members',
            'items.payer',
            'items.location',
        ]);
        $membersById = $room->members->keyBy('id');

        $locatedItems = $room->items->filter(function ($item) {
            if ($item->location === null) {
                return false;
            }

            return $item->location->latitude !== null
                && $item->location->longitude !== null;
        });

        $allLocationsUrl = null;

        if ($locatedItems->isNotEmpty()) {
            $origin = $locatedItems->first()->location;
            $destination = $locatedItems->last()->location;

            $url = 'https://www.google.com/maps/dir/?api=1';
            $url .= '&origin='.$origin->latitude.','.$origin->longitude;
            $url .= '&destination='.$destination->latitude.','.$destination->longitude;

            if ($locatedItems->count() > 2) {
                $waypoints = $locatedItems
                    ->slice(1, max(0, min(9, $locatedItems->count() - 2)))
                    ->map(function ($item) {
                        return $item->location->latitude.','.$item->location->longitude;
                    })
                    ->implode('|');

                if ($waypoints !== '') {
                    $url .= '&waypoints='.$waypoints;
                }
            }

            $url .= '&travelmode=walking';

            $allLocationsUrl = $url;
        }

        if ($allLocationsUrl !== null) {
            Map::create([
                'room_id' => $room->id,
                'url' => $allLocationsUrl,
            ]);
        }

        // 画面表示用の「清算完了メンバー」は、おつり計算の結果と揃える
        $paidMembers = $room->members->filter(function ($member) use ($owedByMember, $changeByMember) {
            $memberId = $member->id;
            $owed = $owedByMember[$memberId] ?? 0;

            // そもそも支払うべき金額がない人は、最初から完了扱い
            if ($owed === 0) {
                return true;
            }

            // 支払義務のある人は、おつりが 0 以上になっていれば完了
            return array_key_exists($memberId, $changeByMember)
                && $changeByMember[$memberId] !== null
                && $changeByMember[$memberId] >= 0;
        })->values();

        /** @var view-string $view */
        $view = 'settlement.show';

        return view($view, [
            'room' => $room,
            'total' => $result['total'],
            'memberDebts' => $result['memberDebts'],
            'transactions' => $result['transactions'],
            'cashInputs' => $cashInputs,
            'changeByMember' => $changeByMember,
            'membersById' => $membersById,
            'locatedItems' => $locatedItems,
            'allLocationsUrl' => $allLocationsUrl,
            'isFullySettled' => $isFullySettled,
            'paidMembers' => $paidMembers,
        ]);
    }

    public function confirm(Request $request, Room $room, SettlementCalculator $calculator)
    {
        $result = $calculator->calculate($room);

        foreach ($result['transactions'] as $transaction) {
            Settlement::create([
                'room_id' => $room->id,
                'payer_id' => $transaction['from'],
                'receiver_id' => $transaction['to'],
                'amount' => $transaction['amount'],
                'is_paid' => false,
            ]);
        }

        return redirect()
            ->route('rooms.settlement.show', ['room' => $room->id])
            ->with('status', 'settlement_confirmed');
    }
}
