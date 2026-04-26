<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryLog extends Model
{
    use HasUuids;

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    // Immutable — no updates or deletes from application code
    protected $fillable = [
        'agent_id',
        'event_type',
        'from_club_id',
        'to_club_id',
        'reason',
        'metadata',
        'event_timestamp',
    ];

    protected function casts(): array
    {
        return [
            'metadata'        => 'array',
            'event_timestamp' => 'datetime',
            'created_at'      => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'agent_id');
    }

    public function fromClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'from_club_id', 'club_id');
    }

    public function toClub(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'to_club_id', 'club_id');
    }
}
