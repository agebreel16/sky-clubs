<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\AppSetting;
use App\Models\Club;
use App\Models\ClubChangeRequest;
use App\Models\DailySnapshot;
use App\Support\DealsApiCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\Pool;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessAgentSelfSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 1;

    public function __construct(public Agent $agent) {}

    public function handle(): void
    {
        if ($this->agent->is_violator) {
            $this->updateSyncTime();
            return;
        }

        $url      = AppSetting::get('deals_api_url');
        $username = AppSetting::get('deals_api_username');
        $password = AppSetting::get('deals_api_password');
        $from     = AppSetting::get('deals_campaign_start_date', '2026-05-17');
        $to       = today()->format('Y-m-d');

        if (! $url || ! $username) {
            $this->updateSyncTime();
            return;
        }

        try {
            $agentId = $this->agent->agent_id;

            $responses = Http::pool(fn (Pool $pool) => [
                'deals' => $pool->as('deals')
                    ->withoutVerifying()
                    ->timeout(15)
                    ->post($url, DealsApiCalculator::buildPayload($username, $password, 'GetSubCustomerDeals', $agentId, $from, $to)),
                'subs' => $pool->as('subs')
                    ->withoutVerifying()
                    ->timeout(15)
                    ->post($url, DealsApiCalculator::buildPayload($username, $password, 'GetSubCustomerActiveSubs', $agentId, $from, $to)),
            ]);

            $dealsResponse = $responses['deals'];
            $subsResponse  = $responses['subs'];

            if (! $dealsResponse || $dealsResponse instanceof \Exception) {
                Log::warning("AgentSelfSync: فشل طلب GetSubCustomerDeals للوكيل {$agentId}");
                $this->updateSyncTime();
                return;
            }

            if (! $subsResponse || $subsResponse instanceof \Exception) {
                Log::warning("AgentSelfSync: فشل طلب GetSubCustomerActiveSubs للوكيل {$agentId}");
                $this->updateSyncTime();
                return;
            }

            $dealsBody = $dealsResponse->json();
            if (! DealsApiCalculator::isSuccess($dealsBody)) {
                Log::warning("AgentSelfSync: GetSubCustomerDeals لم يُرجع SUCCESS للوكيل {$agentId}");
                $this->updateSyncTime();
                return;
            }

            $subsBody = $subsResponse->json();
            if (! DealsApiCalculator::isSuccess($subsBody)) {
                Log::warning("AgentSelfSync: GetSubCustomerActiveSubs لم يُرجع SUCCESS للوكيل {$agentId}");
                $this->updateSyncTime();
                return;
            }

            $transfers  = DealsApiCalculator::extractTransferCount($dealsBody);
            $activeSubs = DealsApiCalculator::extractActiveSubs($subsBody);

            $clubs = Club::where('is_active', true)->orderBy('club_order')->get();

            Agent::withoutEvents(function () use ($activeSubs, $transfers, $clubs) {
                $this->processRow($activeSubs, $transfers, $clubs);
            });

        } catch (\Exception $e) {
            Log::error("AgentSelfSync فشل للوكيل {$this->agent->agent_id}: " . $e->getMessage());
        }

        $this->updateSyncTime();
    }

    private function processRow(int $activeSubs, int $transfers, $clubs): void
    {
        $agent = $this->agent->fresh();
        if (! $agent) return;

        $totals = DealsApiCalculator::computeTotals($activeSubs, $transfers, (int) $agent->pre_campaign_count, (int) $agent->baseline_count);

        $agent->update([
            'transfer_count' => $totals['transfer_count'],
            'new_line_count' => $totals['new_line_count'],
            'current_total'  => $totals['current_total'],
        ]);

        // تحديث snapshot اليوم إن وُجد من import سابق — لا ننشئ snapshot جديداً لأن import_id NOT NULL
        DailySnapshot::where('data_date', today())
            ->where('agent_id', $agent->agent_id)
            ->update([
                'current_total'   => $totals['current_total'],
                'transfer_count'  => $totals['transfer_count'],
                'new_line_count'  => $totals['new_line_count'],
                'club_id_at_date' => $agent->current_club_id,
            ]);

        $agent->refresh();
        $campaignIncrease = $agent->campaign_increase;

        $bestClub = null;
        foreach ($clubs as $club) {
            if ($campaignIncrease >= (int) $club->required_increase
                && $agent->transfer_count >= (int) $club->required_transfer_count) {
                $bestClub = $club;
            }
        }

        $currentClub  = $agent->club;
        $currentOrder = $currentClub ? (int) $currentClub->club_order : 0;
        $newOrder     = $bestClub    ? (int) $bestClub->club_order    : 0;

        $changeType = $newOrder > $currentOrder
            ? 'promotion'
            : (($currentClub && $newOrder < $currentOrder) ? 'demotion' : null);

        $snapshot = [
            'campaign_increase' => $campaignIncrease,
            'transfer_count'    => $agent->transfer_count,
            'new_line_count'    => $agent->new_line_count,
            'current_total'     => $agent->current_total,
            'transfer_pct'      => ($currentClub && $currentClub->required_increase > 0)
                ? round($agent->transfer_count / $currentClub->required_increase * 100, 1)
                : 0,
        ];

        // مصالحة/إنشاء طلب تغيير النادي — تتعافى تلقائياً من تصادم التزامن (استيراد
        // الأدمن الجماعي قد يعالج نفس الوكيل في نفس اللحظة عبر عملية PHP منفصلة).
        // ملاحظة: هذا يُلغي أيضاً أي طلب معلّق شاذ عندما لا يعد الوكيل مؤهلاً لأي
        // تغيير ($changeType === null)، بما يطابق سلوك ProcessDataImport.
        ClubChangeRequest::syncPendingRequest(
            $agent,
            $changeType,
            $changeType === 'promotion' ? $agent->current_club_id : $currentClub?->club_id,
            $changeType === 'promotion' ? $bestClub->club_id      : $bestClub?->club_id,
            $snapshot,
            null
        );
    }

    private function updateSyncTime(): void
    {
        DB::table('agents')
            ->where('agent_id', $this->agent->agent_id)
            ->update(['last_self_sync_at' => now()]);
    }
}
