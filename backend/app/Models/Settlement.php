<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends Model
{
    use HasFactory;

    protected $table = 't_settlements';

    protected $fillable = [
        'room_id',
        'payer_id',
        'receiver_id',
        'amount',
        'is_paid',
    ];

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'payer_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'receiver_id');
    }
}
