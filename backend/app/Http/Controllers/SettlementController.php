<?php

namespace App\Http\Controllers;

use App\Models\Map;
use App\Models\Room;
use App\Models\Settlement;
use App\Services\SettlementCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementController extends Controller
{
    public function show(Request $request, Room $room, SettlementCalculator $calculator): View|RedirectResponse
    {
        $queryKey = $request->query('query_key');
        if ($queryKey === null || $queryKey !== $room->password_plan) {
            return redirect()->route('rooms.show', ['room' => $room]);
        }

        $result = $calculator->calculate($room);

        $cashInputs = $request->input('cash', []);
        $hasCashInputs = collect($cashInputs)->contains(
            static fn ($value): bool => $value !== null && $value !== ''
        );

        $changeByMember = $calculator->calculateChange(
            $result['transactions'],
            $cashInputs,
        );

        // おつり入力があるときのみ、現在 version の is_paid を更新（payer 側で判定）
        if ($room->settlement_version > 0 && $hasCashInputs) {
            foreach ($changeByMember as $memberId => $change) {
                if ($change !== null && $change >= 0) {
                    Settlement::where('room_id', $room->id)
                        ->where('version', $room->settlement_version)
                        ->where('payer_id', $memberId)
                        ->update(['is_paid' => true]);
                }
            }
        }

        $settlementsByPayer = collect();
        if ($room->settlement_version > 0) {
            $settlementsByPayer = Settlement::where('room_id', $room->id)
                ->where('version', $room->settlement_version)
                ->get()
                ->groupBy('payer_id');
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

            if ($hasCashInputs) {
                // おつり入力があるときは、入力値ベースで判定
                if (
                    ! array_key_exists($memberId, $changeByMember)
                    || $changeByMember[$memberId] === null
                    || $changeByMember[$memberId] < 0
                ) {
                    $isFullySettled = false;

                    break;
                }
            } elseif ($room->settlement_version > 0) {
                // 入力がない表示では、保存済み清算の is_paid を参照
                $payerSettlements = $settlementsByPayer->get($memberId, collect());
                if ($payerSettlements->isEmpty() || $payerSettlements->contains(fn ($s): bool => ! $s->is_paid)) {
                    $isFullySettled = false;

                    break;
                }
            } else {
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

        $locatedItems = $room->items->filter(fn ($item) => $item->location !== null);

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

        // 画面表示用の「清算完了メンバー」
        $paidMembers = $room->members->filter(
            function ($member) use (
                $owedByMember,
                $changeByMember,
                $hasCashInputs,
                $room,
                $settlementsByPayer
            ) {
                $memberId = $member->id;
                $owed = $owedByMember[$memberId] ?? 0;

                // そもそも支払うべき金額がない人は、最初から完了扱い
                if ($owed === 0) {
                    return true;
                }

                if ($hasCashInputs) {
                    // 支払義務のある人は、おつりが 0 以上になっていれば完了
                    return array_key_exists($memberId, $changeByMember)
                        && $changeByMember[$memberId] !== null
                        && $changeByMember[$memberId] >= 0;
                }

                if ($room->settlement_version > 0) {
                    $payerSettlements = $settlementsByPayer->get($memberId, collect());

                    return
                        $payerSettlements->isNotEmpty()
                        &&
                        ! $payerSettlements->contains(fn ($s): bool => ! $s->is_paid);
                }

                return false;
            }
        )->values();

        /** @var view-string $view */
        $view = 'settlement.show';

        return view($view, [
            'room' => $room,
            'query_key' => $queryKey,
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
        $cashInputs = $request->input('cash', []);
        $changeByMember = $calculator->calculateChange(
            $result['transactions'],
            $cashInputs,
        );

        DB::transaction(function () use ($room, $result, $changeByMember): void {
            $locked = Room::query()->lockForUpdate()->findOrFail($room->id);

            Settlement::where('room_id', $locked->id)->delete();

            $locked->increment('settlement_version');
            $locked->refresh();

            $version = $locked->settlement_version;

            foreach ($result['transactions'] as $transaction) {
                Settlement::create([
                    'room_id' => $locked->id,
                    'payer_id' => $transaction['from'],
                    'receiver_id' => $transaction['to'],
                    'amount' => $transaction['amount'],
                    'is_paid' => array_key_exists($transaction['from'], $changeByMember)
                        && $changeByMember[$transaction['from']] !== null
                        && $changeByMember[$transaction['from']] >= 0,
                    'version' => $version,
                ]);
            }
        });

        return redirect()
            ->route('rooms.settlement.show', [
                'room' => $room->id,
                'query_key' => $room->password_plan,
            ])
            ->with('status', 'settlement_confirmed');
    }

    public function exportSettlementCsv(Request $request, Room $room): StreamedResponse|RedirectResponse
    {
        $queryKey = $request->query('query_key');
        if ($queryKey === null || $queryKey !== $room->password_plan) {
            return redirect()->route('rooms.show', ['room' => $room]);
        }

        $settlements = Settlement::where('room_id', $room->id)
            ->with(['payer', 'receiver'])
            ->orderBy('id')
            ->get();
        /** @var \Illuminate\Database\Eloquent\Collection<int, Settlement> $settlements */
        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '_', $room->room_name);
        $safeName = trim($safeName) !== '' ? trim($safeName) : 'room';
        $filename = $safeName.'_settlements.csv';

        $callback = function () use ($settlements): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['ID', '清算バージョン', 'お金を返す人', 'お金を受け取る人', '金額', '清算完了済みか']);
            foreach ($settlements as $s) {
                fputcsv($handle, [
                    $s->id,
                    $s->version,
                    $s->payer->member_name ?? '',
                    $s->receiver->member_name ?? '',
                    $s->amount,
                    $s->is_paid ? '済' : '未',
                ]);
            }
            fclose($handle);
        };

        return new StreamedResponse($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
