<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemParticipant;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * シーディング後に item の room_id と payer_id の整合性を検証する.
     */
    public function test_items_payer_belongs_to_same_room(): void
    {
        $this->seed();

        $invalidItems = Item::query()
            ->join('members as payer', 'items.payer_id', '=', 'payer.id')
            ->whereColumn('items.room_id', '!=', 'payer.room_id')
            ->count();

        $this->assertSame(
            0,
            $invalidItems,
            'items の room_id と payer_id の member.room_id が一致しないレコードがあります。'
        );
    }

    /**
     * シーディング後に item_participants の member が item と同じ room に属しているか検証する.
     */
    public function test_item_participants_member_belongs_to_same_room_as_item(): void
    {
        $this->seed();

        $invalidParticipants = ItemParticipant::query()
            ->join('items', 'item_participants.item_id', '=', 'items.id')
            ->join('members as participant', 'item_participants.member_id', '=', 'participant.id')
            ->whereColumn('items.room_id', '!=', 'participant.room_id')
            ->count();

        $this->assertSame(
            0,
            $invalidParticipants,
            'item_participants の member_id が item の属する room と異なるレコードがあります。'
        );
    }
}
