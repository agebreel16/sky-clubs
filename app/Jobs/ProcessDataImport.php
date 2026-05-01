<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Club;
use App\Models\DataImport;
use App\Models\DailySnapshot;
use App\Models\HistoryLog;
use App\Models\Opportunity;
use App\Models\Reward;
use App\Models\AgentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessDataImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public DataImport $import)
    {
    }

    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);
        $startTime = microtime(true);

        $stats = [
            'total'      => 0,
            'processed'  => 0,
            'rejected'   => 0,
            'promotions' => 0,
            'demotions'  => 0,
            'warnings'   => 0,
            'errors'     => 0,
        ];

        try {
            $clubs   = Club::where('is_active', true)->orderBy('club_order', 'asc')->get();
            $dataRows = $this->readExcelFile();
            $stats['total'] = count($dataRows);

            DB::transaction(function () use ($dataRows, $clubs, &$stats) {
                Agent::withoutEvents(function () use ($dataRows, $clubs, &$stats) {
                    foreach ($dataRows as $rowIndex => $row) {
                        try {
                            $this->processAgentRow($row, $clubs, $stats);
                        } catch (\Exception $e) {
                            Log::error("Row {$rowIndex} error: " . $e->getMessage(), ['row' => $row]);
                            $stats['errors']++;
                            $stats['rejected']++;
                        }
                    }
                });
            });

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $this->import->update([
                'status'                 => 'success',
                'total_agents'           => $stats['total'],
                'processed'              => $stats['processed'],
                'rejected'               => $stats['rejected'],
                'promotions_count'       => $stats['promotions'],
                'demotions_count'        => $stats['demotions'],
                'warnings_count'         => $stats['warnings'],
                'errors_count'           => $stats['errors'],
                'processing_duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            $this->import->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Read and parse the Excel file.
     * Expected columns (header row 1):
     *   A: agent_id        (UUID) OR agent_name (string)
     *   B: current_total   (integer)
     *   C: transfer_count  (integer)
     *   D: new_line_count  (integer)
     */
    protected function readExcelFile(): array
    {
        $filePath = Storage::disk('local')->path($this->import->stored_filepath);

        if (!file_exists($filePath)) {
            throw new \RuntimeException("ملف Excel غير موجود: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw new \RuntimeException('الملف لا يحتوي على بيانات (صف البيانات يبدأ من السطر 2)');
        }

        // Normalize headers from row 1
        $headerRow = array_map(function ($h) {
            return strtolower(trim((string) $h));
        }, $rows[1]);

        // Map column letters to header names
        $colMap = [];
        foreach ($headerRow as $col => $name) {
            $colMap[$name] = $col;
        }

        $required = ['agent_id', 'current_total', 'transfer_count', 'new_line_count'];
        foreach ($required as $req) {
            if (!isset($colMap[$req])) {
                throw new \RuntimeException("عمود مفقود في ملف Excel: '{$req}'");
            }
        }

        $data = [];
        for ($i = 2; $i <= count($rows); $i++) {
            if (!isset($rows[$i])) {
                continue;
            }

            $row = $rows[$i];

            $agentId      = trim((string) ($row[$colMap['agent_id']]      ?? ''));
            $currentTotal = (int)         ($row[$colMap['current_total']]  ?? 0);
            $transferCnt  = (int)         ($row[$colMap['transfer_count']] ?? 0);
            $newLineCnt   = (int)         ($row[$colMap['new_line_count']] ?? 0);

            // Skip completely empty rows
            if (empty($agentId) && $currentTotal === 0) {
                continue;
            }

            $data[] = [
                'agent_id'      => $agentId,
                'current_total' => $currentTotal,
                'transfer_count'=> $transferCnt,
                'new_line_count'=> $newLineCnt,
            ];
        }

        return $data;
    }

    protected function processAgentRow(array $row, $clubs, array &$stats): void
    {
        // Find agent by ID or name
        $agent = Agent::find($row['agent_id']);
        if (!$agent) {
            $agent = Agent::where('agent_name', $row['agent_id'])->first();
        }

        if (!$agent) {
            Log::warning("Agent not found: " . $row['agent_id']);
            $stats['rejected']++;
            return;
        }

        // Update agent stats
        $agent->update([
            'current_total'  => max($agent->pre_campaign_count, (int) $row['current_total']),
            'transfer_count' => max(0, (int) $row['transfer_count']),
            'new_line_count' => max(0, (int) $row['new_line_count']),
        ]);

        // AuditLog يدوي (Observer متوقف داخل withoutEvents)
        $changes = $agent->getChanges();
        unset($changes['updated_at']);
        if (!empty($changes)) {
            \App\Models\AuditLog::create([
                'user_id'     => $this->import->uploaded_by,
                'action'      => 'update',
                'model_type'  => 'Agent',
                'model_id'    => $agent->agent_id,
                'old_values'  => array_intersect_key($agent->getOriginal(), $changes),
                'new_values'  => $changes,
                'ip_address'  => '0.0.0.0',
                'user_agent'  => 'import-job',
                'description' => 'تحديث بيانات الوكيل عبر Import: ' . $this->import->import_id,
                'status'      => 'success',
            ]);
        }

        // Save daily snapshot
        DailySnapshot::create([
            'import_id'          => $this->import->import_id,
            'data_date'          => $this->import->data_date,
            'agent_id'           => $agent->agent_id,
            'baseline_count'     => $agent->baseline_count,
            'pre_campaign_count' => $agent->pre_campaign_count,
            'current_total'      => $agent->current_total,
            'transfer_count'     => $agent->transfer_count,
            'new_line_count'     => $agent->new_line_count,
            'club_id_at_date'    => $agent->current_club_id,
        ]);

        $stats['processed']++;

        $agent->refresh();
        $campaignIncrease = $agent->current_total - $agent->pre_campaign_count;

        // Find best qualified club
        $bestClub = null;
        foreach ($clubs as $club) {
            if ($campaignIncrease >= (int) $club->required_increase) {
                $bestClub = $club;
            }
        }

        $currentClub  = $agent->club;
        $currentOrder = $currentClub ? (int) $currentClub->club_order : 0;
        $newOrder     = $bestClub    ? (int) $bestClub->club_order    : 0;

        // ── PROMOTION ────────────────────────────────────────────────────────
        if ($newOrder > $currentOrder) {
            $isFirst = Agent::where('current_club_id', $bestClub->club_id)->count() < $bestClub->first_arrival_count;

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
                'agent_id'         => $agent->agent_id,
                'club_id'          => $bestClub->club_id,
                'amount'           => $bestClub->base_reward_amount,
                'is_first_arrival' => false,
                'payment_status'   => 'pending',
            ]);

            if ($isFirst) {
                Reward::create([
                    'agent_id'         => $agent->agent_id,
                    'club_id'          => $bestClub->club_id,
                    'amount'           => $bestClub->first_arrival_reward_amount,
                    'is_first_arrival' => true,
                    'payment_status'   => 'pending',
                ]);
            }

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

            if ($isFirst) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $bestClub->club_id,
                    'type'        => 'first_arrival',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            $stats['promotions']++;
        }

        // ── DEMOTION TIMER ────────────────────────────────────────────────────
        elseif ($currentClub && $newOrder < $currentOrder) {
            $transferPct = $currentClub->required_increase > 0
                ? ($agent->transfer_count / $currentClub->required_increase) * 100
                : 100;

            if ($transferPct < 60 && $agent->demotion_timer_start === null) {
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

                AgentNotification::create([
                    'agent_id'          => $agent->agent_id,
                    'club_id'           => $agent->current_club_id,
                    'notification_type' => 'warning',
                    'title'             => 'تحذير: عداد التهبيط بدأ',
                    'message'           => 'نسبة التحويل انخفضت عن 60%. لديك مهلة للتحسين.',
                    'category'          => 'in_club',
                    'sent_at'           => now(),
                ]);

                $stats['warnings']++;
            } elseif ($agent->demotion_timer_start !== null) {
                $daysElapsed = (int) $agent->demotion_timer_start->diffInDays(now());
                if ($daysElapsed >= $currentClub->demotion_timer_days) {
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

                    $stats['demotions']++;
                }
            }
        }

        // ── RECOVERY (reset timer if improved) ────────────────────────────────
        elseif ($currentClub && $agent->demotion_timer_start !== null) {
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

        // ── BONUS OPPORTUNITIES (Peak Club) ───────────────────────────────────
        if ($bestClub && $bestClub->has_bonus_opportunities && $bestClub->bonus_per_numbers > 0) {
            $bonusCount = (int) floor($agent->new_line_count / $bestClub->bonus_per_numbers);
            $existing   = $agent->opportunities()
                ->where('club_id', $bestClub->club_id)
                ->where('type', 'bonus')
                ->count();

            for ($i = $existing; $i < $bonusCount; $i++) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $bestClub->club_id,
                    'type'        => 'bonus',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }
        }
    }
}
