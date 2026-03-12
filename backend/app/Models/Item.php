<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Member $payer
 * @property-read \App\Models\ItemCategory|null $category
 * @property-read \App\Models\Location|null $location
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\ItemParticipant> $participants
 */
class Item extends Model
{
    use HasFactory;

    protected $table = 't_items';

    protected $fillable = [
        'room_id',
        'item_name',
        'memo',
        'amount',
        'paid_at',
        'category_id',
        'payer_id',
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'payer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function location()
    {
        return $this->hasOne(Location::class, 'item_id');
    }

    public function participants()
    {
        return $this->hasMany(ItemParticipant::class, 'item_id');
    }
}
