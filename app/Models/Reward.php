<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reward extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'reward_id';

    protected $fillable = [
        'agent_id',
        'club_id',
        'amount',
        'is_first_arrival',
        'payment_status',
        'paid_date',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'is_first_arrival' => 'boolean',
            'paid_date'        => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'agent_id');
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'club_id', 'club_id');
    }
}
