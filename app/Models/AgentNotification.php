<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgentNotification extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'notifications';

    protected $primaryKey = 'notification_id';

    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'notification_type',
        'title',
        'body',
        'category',
        'stage',
        'current_count',
        'required_count',
        'club_id',
        'is_read',
        'sent_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'sent_at'    => 'datetime',
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
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
