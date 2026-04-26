<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agent extends Model
{
    use HasUuids, SoftDeletes;

    protected $primaryKey = 'agent_id';

    protected $fillable = [
        'agent_name',
        'baseline_count',
        'pre_campaign_count',
        'current_total',
        'transfer_count',
        'new_line_count',
        'current_club_id',
        'entry_date',
        'demotion_timer_start',
        'is_first_arrival',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'entry_date'           => 'datetime',
            'demotion_timer_start' => 'datetime',
            'is_first_arrival'     => 'boolean',
        ];
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class, 'current_club_id', 'club_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'agent_id', 'agent_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'agent_id', 'agent_id');
    }

    public function historyLogs(): HasMany
    {
        return $this->hasMany(HistoryLog::class, 'agent_id', 'agent_id');
    }

    public function dailySnapshots(): HasMany
    {
        return $this->hasMany(DailySnapshot::class, 'agent_id', 'agent_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AgentNotification::class, 'agent_id', 'agent_id');
    }

    public function getCampaignIncreaseAttribute(): int
    {
        return $this->current_total - $this->pre_campaign_count;
    }

    public function getTransferPercentageAttribute(): float
    {
        $required = $this->club ? $this->club->required_increase : 0;
        if ($required === 0) {
            return 0.0;
        }

        return round(($this->transfer_count / $required) * 100, 2);
    }

    public function getBaselineLossAttribute(): int
    {
        return $this->baseline_count - $this->pre_campaign_count;
    }
}
