<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasUuids;

    protected $primaryKey = 'club_id';

    protected $fillable = [
        'club_name',
        'club_order',
        'required_increase',
        'required_transfer_count',
        'required_transfer_percentage',
        'base_reward_amount',
        'first_arrival_reward_amount',
        'first_arrival_count',
        'seat_capacity',
        'grand_prize_amount',
        'entry_opportunities',
        'has_bonus_opportunities',
        'bonus_per_numbers',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_transfer_percentage' => 'decimal:2',
            'base_reward_amount'           => 'decimal:2',
            'first_arrival_reward_amount'  => 'decimal:2',
            'grand_prize_amount'           => 'decimal:2',
            'has_bonus_opportunities'      => 'boolean',
            'is_active'                    => 'boolean',
        ];
    }

    public function agents(): HasMany
    {
        return $this->hasMany(Agent::class, 'current_club_id', 'club_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'club_id', 'club_id');
    }

    public function rewards(): HasMany
    {
        return $this->hasMany(Reward::class, 'club_id', 'club_id');
    }

    public function historyLogsFrom(): HasMany
    {
        return $this->hasMany(HistoryLog::class, 'from_club_id', 'club_id');
    }

    public function historyLogsTo(): HasMany
    {
        return $this->hasMany(HistoryLog::class, 'to_club_id', 'club_id');
    }

    public function getActiveAgentsCountAttribute(): int
    {
        return $this->agents()->count();
    }

    public function getLotteryUnlockedAttribute(): bool
    {
        return $this->getActiveAgentsCountAttribute() >= $this->seat_capacity;
    }

    /**
     * نقاط جاهزية الوكيل لهذا النادي من 100 — 50 نقطة لكل من شرطي الترقية
     * (إجمالي الزيادة، خطوط التحويل)، تناسبياً حسب مدى تحقيق المطلوب.
     * نفس صيغة النقاط المستخدمة ببطاقات "المحافظة/الهدف القادم" في بوابة الوكيل.
     */
    public function readinessScoreFor(Agent $agent): int
    {
        $reqInc   = (int) $this->required_increase;
        $reqTrans = (int) ($this->required_transfer_count ?? 0);
        $incVal   = (int) $agent->campaign_increase;
        $transVal = (int) $agent->transfer_count;

        $incPts   = $reqInc > 0 ? min(50, (int) round(50 * $incVal / $reqInc)) : 50;
        $transPts = $reqTrans > 0 ? min(50, (int) round(50 * $transVal / $reqTrans)) : 50;

        return $incPts + $transPts;
    }
}
