<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemParticipant;
use App\Models\Location;
use App\Models\Member;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function create(): View
    {
        /** @var view-string $view */
        $view = 'room.create';

        return view($view);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
        ]);

        $room = Room::create([
            'room_name' => $validated['room_name'],
            'password_plan' => bin2hex(random_bytes(4)),
        ]);

        return redirect()->route('rooms.show', $room);
    }

    public function show(Room $room): View
    {
        $room->load([
            'members',
            'members.itemParticipants',
            'items.payer',
            'items.participants.member',
            'items.location',
        ]);

        $totalAmount = (int) $room->items->sum('amount');

        $totals = [];

        foreach ($room->members as $member) {
            $totals[$member->id] = 0;
        }

        foreach ($room->items as $item) {
            if (! isset($totals[$item->payer_id])) {
                continue;
            }

            $totals[$item->payer_id] += $item->amount;
        }

        $ranking = collect($totals)
            ->map(fn (int $total, int $memberId) => [
                'member' => $room->members->firstWhere('id', $memberId),
                'total' => $total,
            ])
            ->filter(fn (array $entry) => $entry['member'] !== null)
            ->sortByDesc('total')
            ->values()
            ->all();

        /** @var view-string $view */
        $view = 'room.show';

        return view($view, [
            'room' => $room,
            'totalAmount' => $totalAmount,
            'ranking' => $ranking,
        ]);
    }

    public function addMember(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'member_name' => ['required', 'string', 'max:255'],
        ]);

        Member::create([
            'member_name' => $validated['member_name'],
            'room_id' => $room->id,
        ]);

        return redirect()->route('rooms.show', $room);
    }

    public function addItem(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'payer_id' => ['required', 'integer', 'exists:t_members,id'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:t_members,id'],
            'memo' => ['nullable', 'string', 'max:1000'],
            'paid_at' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'location_address' => ['nullable', 'string', 'max:255'],
        ]);

        $item = Item::create([
            'room_id' => $room->id,
            'item_name' => $validated['item_name'],
            'memo' => $validated['memo'] ?? null,
            'amount' => $validated['amount'],
            'paid_at' => $validated['paid_at'] ?? now()->toDateString(),
            'category_id' => null,
            'payer_id' => $validated['payer_id'],
        ]);

        $participants = $validated['participant_ids'];
        $perPerson = (int) round($validated['amount'] / max(1, count($participants)));

        foreach ($participants as $memberId) {
            ItemParticipant::create([
                'share_amount' => $perPerson,
                'item_id' => $item->id,
                'member_id' => $memberId,
            ]);
        }

        if (! empty($validated['latitude']) && ! empty($validated['longitude'])) {
            $lat = (float) $validated['latitude'];
            $lng = (float) $validated['longitude'];

            $url = sprintf('https://www.google.com/maps?q=%F,%F&z=17', $lat, $lng);

            Location::create([
                'latitude' => $lat,
                'longitude' => $lng,
                'url_map' => $url,
                'item_id' => $item->id,
            ]);
        }

        return redirect()->route('rooms.show', $room);
    }
}
