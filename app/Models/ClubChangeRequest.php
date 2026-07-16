<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;

class ClubChangeRequest extends Model
{
    use HasUuids;

    protected $primaryKey = 'id';

    protected $fillable = [
        'agent_id',
        'import_id',
        'from_club_id',
        'to_club_id',
        'change_type',
        'agent_stats_snapshot',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'approval_note',
    ];

    protected function casts(): array
    {
        return [
            'agent_stats_snapshot' => 'array',
            'reviewed_at'          => 'datetime',
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

    public function import(): BelongsTo
    {
        return $this->belongsTo(DataImport::class, 'import_id', 'import_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPromotion(): bool
    {
        return $this->change_type === 'promotion';
    }

    /**
     * يبحث عن طلب pending موجود لنفس الوكيل ويحدّثه، أو يلغيه وينشئ طلباً جديداً،
     * أو يلغي أي طلب شاذ إذا لم يعد الوكيل مؤهلاً لأي تغيير ($changeType === null).
     * مشتركة بين ProcessDataImport::processAgentRow() وProcessAgentSelfSync::processRow().
     *
     * تتعافى من تصادم قيد UNIQUE (uniq_ccr_pending_agent) في حال ربح process آخر
     * السباق بين قراءة "لا يوجد pending" وإدراج صف جديد لنفس الوكيل.
     */
    public static function syncPendingRequest(
        Agent $agent,
        ?string $changeType,
        ?string $fromClubId,
        ?string $toClubId,
        array $snapshot,
        ?string $importId = null
    ): ?self {
        $existingPending = static::where('agent_id', $agent->agent_id)
            ->where('status', 'pending')
            ->first();

        if ($changeType === null) {
            $existingPending?->update(['status' => 'auto_cancelled']);
            return null;
        }

        if ($existingPending) {
            if ($existingPending->change_type === $changeType
                && $existingPending->to_club_id === $toClubId) {
                $existingPending->update(['agent_stats_snapshot' => $snapshot]);
                return $existingPending;
            }
            $existingPending->update(['status' => 'auto_cancelled']);
        }

        $payload = [
            'agent_id'             => $agent->agent_id,
            'import_id'            => $importId,
            'from_club_id'         => $fromClubId,
            'to_club_id'           => $toClubId,
            'change_type'          => $changeType,
            'agent_stats_snapshot' => $snapshot,
            'status'               => 'pending',
        ];

        try {
            return static::create($payload);
        } catch (QueryException $e) {
            if (! static::isPendingUniqueViolation($e)) {
                throw $e;
            }

            // خسرنا السباق: process آخر أدرج صف pending لنفس الوكيل بين الـ SELECT
            // أعلاه وهذا الـ INSERT. أعد الجلب وحدّث اللقطة بدلاً من فشل العملية.
            $race = static::where('agent_id', $agent->agent_id)
                ->where('status', 'pending')
                ->first();

            if (! $race) {
                throw $e;
            }

            $race->update(['agent_stats_snapshot' => $snapshot]);
            return $race;
        }
    }

    private static function isPendingUniqueViolation(QueryException $e): bool
    {
        // MySQL ER_DUP_ENTRY (1062)، مقيَّد باسم الفهرس المحدَّد لتجنّب ابتلاع أي
        // خطأ قيد آخر (مثل foreign key) يظهر أيضاً بنفس SQLSTATE 23000 العام.
        return (($e->errorInfo[1] ?? null) === 1062)
            && str_contains($e->getMessage(), 'uniq_ccr_pending_agent');
    }
}
