<?php

namespace App\Observers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Club;
use App\Models\HistoryLog;
use App\Models\Opportunity;
use App\Models\Reward;
use App\Models\AgentNotification;

class AgentObserver
{
    public function updated(Agent $agent): void
    {
        // 1. Detect Club Change (Promotion / Demotion) — للتعديل المباشر على current_club_id
        if ($agent->wasChanged('current_club_id')) {
            $this->handleClubChange($agent);
        }

        // 2. Detect Demotion Timer Start
        if ($agent->wasChanged('demotion_timer_start') && $agent->demotion_timer_start !== null) {
            $this->handleDemotionTimerStart($agent);
        }

        // 3. Audit Log (للتعديلات اليدوية — Import يعمل بـ withoutEvents فلا يصل هنا)
        $this->logAudit($agent);

        // 4. إعادة تقييم النادي إذا تغيّرت الأرقام (تعديل يدوي من Admin Panel)
        $statsChanged = $agent->wasChanged([
            'current_total', 'transfer_count', 'new_line_count', 'pre_campaign_count',
        ]);

        if ($statsChanged) {
            $this->checkAndApplyClubChanges($agent);
        }
    }

    private function handleClubChange(Agent $agent): void
    {
        $fromClubId = $agent->getOriginal('current_club_id');
        $toClubId   = $agent->current_club_id;

        $fromClub = $fromClubId ? Club::find($fromClubId) : null;
        $toClub   = $toClubId   ? Club::find($toClubId)   : null;

        // Determine event type
        if (!$toClub) {
            $eventType = 'demotion';
        } elseif (!$fromClub) {
            $eventType = 'promotion';
        } else {
            $eventType = $toClub->club_order > $fromClub->club_order ? 'promotion' : 'demotion';
        }

        // Log to History
        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => $eventType,
            'from_club_id'    => $fromClubId,
            'to_club_id'      => $toClubId,
            'reason'          => $eventType === 'promotion' ? 'تحقيق شروط الترقية' : 'عدم الحفاظ على النشاط',
            'event_timestamp' => now(),
        ]);

        // Generate Reward & Opportunities on Promotion
        if ($eventType === 'promotion' && $toClub) {
            // Base reward
            Reward::create([
                'agent_id'       => $agent->agent_id,
                'club_id'        => $toClubId,
                'amount'         => $toClub->base_reward_amount,
                'is_first_arrival' => false,
                'payment_status' => 'pending',
            ]);

            // First arrival bonus
            if ($agent->is_first_arrival) {
                Reward::create([
                    'agent_id'        => $agent->agent_id,
                    'club_id'         => $toClubId,
                    'amount'          => $toClub->first_arrival_reward_amount,
                    'is_first_arrival' => true,
                    'payment_status'  => 'pending',
                ]);
            }

            // Entry opportunities (lottery tickets)
            $entryCount = $toClub->entry_opportunities ?? 1;
            for ($i = 0; $i < $entryCount; $i++) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClubId,
                    'type'        => 'entry',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            // First arrival opportunity
            if ($agent->is_first_arrival) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClubId,
                    'type'        => 'first_arrival',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }
        }

        // Notification
        AgentNotification::create([
            'agent_id'          => $agent->agent_id,
            'club_id'           => $toClubId,
            'notification_type' => $eventType,
            'title'             => $eventType === 'promotion' ? 'مبروك الترقية!' : 'تنبيه: هبوط النادي',
            'message'           => $eventType === 'promotion'
                ? "تهانينا! لقد انضممت إلى {$toClub->club_name}."
                : 'نأسف لإبلاغك بالهبوط.' . ($toClub ? " إلى {$toClub->club_name}" : ' خارج الأندية.'),
            'category'          => $toClubId ? 'in_club' : 'outside_clubs',
            'sent_at'           => now(),
        ]);
    }

    private function handleDemotionTimerStart(Agent $agent): void
    {
        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => 'warning',
            'from_club_id'    => null,
            'to_club_id'      => $agent->current_club_id,
            'reason'          => 'نزول نسبة التحويل عن 60%',
            'event_timestamp' => now(),
        ]);

        AgentNotification::create([
            'agent_id'          => $agent->agent_id,
            'club_id'           => $agent->current_club_id,
            'notification_type' => 'warning',
            'title'             => 'تحذير: عداد التهبيط بدأ',
            'message'           => 'نسبة التحويل انخفضت. لديك مهلة محددة للتحسين.',
            'category'          => 'in_club',
            'sent_at'           => now(),
        ]);
    }

    private function checkAndApplyClubChanges(Agent $agent): void
    {
        // Reload fresh to avoid stale data
        $agent->refresh();

        $clubs          = Club::where('is_active', true)->orderBy('club_order')->get();
        $campaignIncrease = $agent->current_total - $agent->pre_campaign_count;
        $currentClub    = $agent->club;
        $currentOrder   = $currentClub ? (int) $currentClub->club_order : 0;

        // Find the highest club the agent qualifies for
        $bestClub  = null;
        foreach ($clubs as $club) {
            if ($campaignIncrease >= (int) $club->required_increase) {
                $bestClub = $club;
            }
        }

        $newOrder = $bestClub ? (int) $bestClub->club_order : 0;

        // PROMOTION
        if ($newOrder > $currentOrder) {
            $isFirst = Agent::where('current_club_id', $bestClub->club_id)->count() < $bestClub->first_arrival_count;

            // Bypass observer loop with query builder
            Agent::where('agent_id', $agent->agent_id)->update([
                'current_club_id'      => $bestClub->club_id,
                'entry_date'           => now(),
                'demotion_timer_start' => null,
                'is_first_arrival'     => $isFirst,
            ]);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'promotion',
                'from_club_id'    => $agent->current_club_id,
                'to_club_id'      => $bestClub->club_id,
                'reason'          => "تحقيق {$campaignIncrease} خط جديد",
                'event_timestamp' => now(),
            ]);

            Reward::create([
                'agent_id'        => $agent->agent_id,
                'club_id'         => $bestClub->club_id,
                'amount'          => $bestClub->base_reward_amount,
                'is_first_arrival' => false,
                'payment_status'  => 'pending',
            ]);

            $entryCount = $bestClub->entry_opportunities ?? 1;
            for ($i = 0; $i < $entryCount; $i++) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $bestClub->club_id,
                    'type'        => 'entry',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            return;
        }

        // DEMOTION TIMER LOGIC (only for agents already in a club)
        if ($currentClub && $newOrder < $currentOrder) {
            $transferPct = $currentClub->required_increase > 0
                ? ($agent->transfer_count / $currentClub->required_increase) * 100
                : 100;

            if ($transferPct < 60 && $agent->demotion_timer_start === null) {
                // Start timer
                Agent::where('agent_id', $agent->agent_id)->update([
                    'demotion_timer_start' => now(),
                ]);

                HistoryLog::create([
                    'agent_id'        => $agent->agent_id,
                    'event_type'      => 'warning',
                    'from_club_id'    => null,
                    'to_club_id'      => $agent->current_club_id,
                    'reason'          => 'نسبة التحويل أقل من 60%',
                    'event_timestamp' => now(),
                ]);
            } elseif ($agent->demotion_timer_start !== null) {
                $daysElapsed = (int) $agent->demotion_timer_start->diffInDays(now());
                if ($daysElapsed >= $currentClub->demotion_timer_days) {
                    // Execute demotion
                    Agent::where('agent_id', $agent->agent_id)->update([
                        'current_club_id'      => $bestClub ? $bestClub->club_id : null,
                        'demotion_timer_start' => null,
                    ]);

                    HistoryLog::create([
                        'agent_id'        => $agent->agent_id,
                        'event_type'      => 'demotion',
                        'from_club_id'    => $currentClub->club_id,
                        'to_club_id'      => $bestClub ? $bestClub->club_id : null,
                        'reason'          => 'انتهاء مهلة الإنذار',
                        'event_timestamp' => now(),
                    ]);
                }
            }
        } elseif ($currentClub && $agent->demotion_timer_start !== null) {
            // Recovered: reset timer
            Agent::where('agent_id', $agent->agent_id)->update([
                'demotion_timer_start' => null,
            ]);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'achievement',
                'from_club_id'    => null,
                'to_club_id'      => $agent->current_club_id,
                'reason'          => 'استعادة مستوى النشاط المطلوب',
                'event_timestamp' => now(),
            ]);
        }
    }

    private function logAudit(Agent $agent): void
    {
        $changes = $agent->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'update',
            'model_type'  => 'Agent',
            'model_id'    => $agent->agent_id,
            'old_values'  => array_intersect_key($agent->getOriginal(), $changes),
            'new_values'  => $changes,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
            'description' => 'تعديل بيانات الوكيل: ' . implode(', ', array_keys($changes)),
            'status'      => 'success',
        ]);
    }
}
