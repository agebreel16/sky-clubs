<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'opportunity_id';

    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'club_id',
        'type',
        'earned_date',
        'is_active',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'earned_date' => 'datetime',
            'is_active'   => 'boolean',
            'created_at'  => 'datetime',
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
