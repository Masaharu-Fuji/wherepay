<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\Member> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\Map> $maps
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Models\Item> $items
 */
class Room extends Model
{
    use HasFactory;

    protected $table = 't_rooms';

    protected $fillable = [
        'room_name',
        'password_plan',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class, 'room_id');
    }

    public function maps(): HasMany
    {
        return $this->hasMany(Map::class, 'room_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'room_id');
    }
}
