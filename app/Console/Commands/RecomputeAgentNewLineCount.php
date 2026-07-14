<?php

namespace App\Console\Commands;

use App\Models\Agent;
use Illuminate\Console\Command;

/**
 * تصحيح رجعي لـ new_line_count المخزَّن لكل الوكلاء: الصيغة القديمة كانت تحسبه من
 * current_total مباشرة (فيشمل الرصيد القديم بالخطأ)، بدل اشتقاقه من campaign_increase
 * (current_total - baseline_count). يُنفَّذ من بيانات مخزَّنة أصلاً بدون أي استدعاء API.
 */
class RecomputeAgentNewLineCount extends Command
{
    protected $signature   = 'app:recompute-new-line-count';
    protected $description = 'إعادة حساب new_line_count لكل الوكلاء من البيانات المخزَّنة (تصحيح صيغة قديمة كانت تشمل الرصيد التاريخي بالخطأ)';

    public function handle(): int
    {
        $total   = 0;
        $changed = 0;

        Agent::withoutEvents(function () use (&$total, &$changed) {
            Agent::query()
                ->select(['agent_id', 'current_total', 'baseline_count', 'transfer_count', 'new_line_count'])
                ->chunkById(200, function ($agents) use (&$total, &$changed) {
                    foreach ($agents as $agent) {
                        $total++;

                        $campaignIncrease = max(0, $agent->current_total - $agent->baseline_count);
                        $correct          = max(0, $campaignIncrease - $agent->transfer_count);

                        if ($correct !== $agent->new_line_count) {
                            $agent->update(['new_line_count' => $correct]);
                            $changed++;
                        }
                    }
                }, 'agent_id');
        });

        $this->info("تمت مراجعة {$total} وكيل — تصحيح {$changed} سجلاً.");

        return self::SUCCESS;
    }
}
