<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Room $room
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\Item> $paidItems
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\ItemParticipant> $itemParticipants
 */
class Member extends Model
{
    use HasFactory;

    protected $table = 't_members';

    protected $fillable = [
        'member_name',
        'room_id',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function paidItems()
    {
        return $this->hasMany(Item::class, 'payer_id');
    }

    public function itemParticipants()
    {
        return $this->hasMany(ItemParticipant::class, 'member_id');
    }
}
