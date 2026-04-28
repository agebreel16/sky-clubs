<?php

namespace App\Jobs;

use App\Models\Agent;
use App\Models\AgentImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProcessAgentImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public string $importId;

    public function __construct(public AgentImport $import)
    {
        $this->importId = $import->getKey();
    }

    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);
        $startTime = microtime(true);

        $stats = [
            'total'    => 0,
            'created'  => 0,
            'updated'  => 0,
            'rejected' => 0,
            'errors'   => 0,
        ];

        try {
            $rows          = $this->readExcelFile();
            $stats['total'] = count($rows);

            DB::transaction(function () use ($rows, &$stats) {
                foreach ($rows as $index => $row) {
                    try {
                        $this->processRow($row, $stats);
                    } catch (\Exception $e) {
                        Log::error("AgentImport row {$index} error: " . $e->getMessage(), ['row' => $row]);
                        $stats['errors']++;
                        $stats['rejected']++;
                    }
                }
            });

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $this->import->update([
                'status'                 => 'success',
                'total_rows'             => $stats['total'],
                'created_count'          => $stats['created'],
                'updated_count'          => $stats['updated'],
                'rejected_count'         => $stats['rejected'],
                'errors_count'           => $stats['errors'],
                'processing_duration_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            $this->import->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            Log::error('AgentImport failed: ' . $e->getMessage());
        }
    }

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

        $headerRow = array_map(fn ($h) => strtolower(trim((string) $h)), $rows[1]);

        $colMap = [];
        foreach ($headerRow as $col => $name) {
            $colMap[$name] = $col;
        }

        $required = ['agent_id', 'agent_name', 'baseline_count', 'pre_campaign_count'];
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

            $agentId          = trim((string) ($row[$colMap['agent_id']]           ?? ''));
            $agentName        = trim((string) ($row[$colMap['agent_name']]          ?? ''));
            $baselineCount    = (int)         ($row[$colMap['baseline_count']]      ?? 0);
            $preCampaignCount = (int)         ($row[$colMap['pre_campaign_count']]  ?? 0);
            $currentTotal     = isset($colMap['current_total'])
                ? (int) ($row[$colMap['current_total']] ?? $preCampaignCount)
                : $preCampaignCount;
            $transferCount    = isset($colMap['transfer_count'])
                ? (int) ($row[$colMap['transfer_count']] ?? 0)
                : 0;
            $newLineCount     = isset($colMap['new_line_count'])
                ? (int) ($row[$colMap['new_line_count']] ?? 0)
                : 0;

            if (empty($agentId) && empty($agentName)) {
                continue;
            }

            $data[] = compact(
                'agentId', 'agentName', 'baselineCount',
                'preCampaignCount', 'currentTotal', 'transferCount', 'newLineCount'
            );
        }

        return $data;
    }

    protected function processRow(array $row, array &$stats): void
    {
        [
            'agentId'          => $agentId,
            'agentName'        => $agentName,
            'baselineCount'    => $baselineCount,
            'preCampaignCount' => $preCampaignCount,
            'currentTotal'     => $currentTotal,
            'transferCount'    => $transferCount,
            'newLineCount'     => $newLineCount,
        ] = $row;

        // Validate
        if (!Str::isUuid($agentId)) {
            Log::warning("AgentImport: invalid UUID '{$agentId}'");
            $stats['rejected']++;
            return;
        }

        if ($baselineCount <= 0) {
            Log::warning("AgentImport: baseline_count must be > 0 for agent '{$agentId}'");
            $stats['rejected']++;
            return;
        }

        if ($preCampaignCount > $baselineCount) {
            Log::warning("AgentImport: pre_campaign_count > baseline_count for agent '{$agentId}'");
            $stats['rejected']++;
            return;
        }

        $existing = Agent::find($agentId);

        if ($existing) {
            $existing->update([
                'agent_name'        => $agentName,
                'baseline_count'    => $baselineCount,
                'pre_campaign_count'=> $preCampaignCount,
                'current_total'     => $currentTotal,
                'transfer_count'    => $transferCount,
                'new_line_count'    => $newLineCount,
            ]);
            $stats['updated']++;
        } else {
            Agent::create([
                'agent_id'           => $agentId,
                'agent_name'         => $agentName,
                'baseline_count'     => $baselineCount,
                'pre_campaign_count' => $preCampaignCount,
                'current_total'      => $currentTotal,
                'transfer_count'     => $transferCount,
                'new_line_count'     => $newLineCount,
                'agent_import_id'    => $this->importId,
            ]);
            $stats['created']++;
        }
    }
}
