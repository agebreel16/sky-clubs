<?php

namespace App\Filament\Pages;

use App\Exports\AgentDealsReportPdf;
use App\Models\Agent;
use App\Models\AppSetting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgentDealsInspector extends Page
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.agent-deals-inspector';

    protected const MAX_RANGE_DAYS = 120;

    // تاريخ قديم بما يكفي ليشمل كل الخطوط قبل بداية الحملة عند استعلام "حتى تاريخ بداية الحملة"
    protected const LIFETIME_ANCHOR_DATE = '2000-01-01';

    public static function getNavigationLabel(): string  { return 'تقرير مفصل لأرقام وكيل'; }
    public static function getNavigationGroup(): ?string { return 'البيانات والمزامنة'; }
    public static function getNavigationIcon(): string   { return 'heroicon-o-magnifying-glass'; }
    public static function getNavigationSort(): ?int     { return 4; }
    public function getTitle(): string                   { return 'تقرير مفصل لأرقام وكيل'; }

    public ?array $filterData = [];

    public array $reportRows = [];
    public ?string $reportAgentLabel = null;
    public ?string $reportAgentId = null;
    public ?string $reportFrom = null;
    public ?string $reportUntil = null;
    public int $incompleteDaysCount = 0;

    public ?string $campaignStartLabel = null;
    public ?int $preCampaignLineCount = null;
    public ?int $postCampaignLineCount = null;
    public bool $preCampaignFailed = false;
    public bool $postCampaignFailed = false;

    public function mount(): void
    {
        $this->filterData = [
            'agent_id' => null,
            'from'     => today()->subDays(6)->format('Y-m-d'),
            'until'    => today()->format('Y-m-d'),
        ];
    }

    public function filterForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('filterData')
            ->schema([
                Select::make('agent_id')
                    ->label('الوكيل')
                    ->options(fn () => Agent::whereNull('deleted_at')
                        ->orderBy('agent_name')
                        ->get(['agent_id', 'agent_name', 'phone'])
                        ->mapWithKeys(fn (Agent $a) => [$a->agent_id => $a->phone ? "{$a->agent_name} — {$a->phone}" : $a->agent_name]))
                    ->searchable()
                    ->required(),
                DatePicker::make('from')
                    ->label('من تاريخ')
                    ->required(),
                DatePicker::make('until')
                    ->label('إلى تاريخ')
                    ->required(),
            ]);
    }

    protected function getForms(): array
    {
        return ['filterForm'];
    }

    public function fetchReport(): void
    {
        $data = $this->filterForm->getState();

        $agent = Agent::find($data['agent_id']);

        if (! $agent) {
            Notification::make()->title('يرجى اختيار وكيل')->danger()->send();
            return;
        }

        $from  = Carbon::parse($data['from'])->startOfDay();
        $until = Carbon::parse($data['until'])->startOfDay();

        if ($until->lt($from)) {
            Notification::make()->title('تاريخ "إلى" يجب أن يكون بعد تاريخ "من"')->danger()->send();
            return;
        }

        if ($from->diffInDays($until) > self::MAX_RANGE_DAYS) {
            Notification::make()
                ->title('الفترة طويلة جداً')
                ->body('الحد الأقصى ' . self::MAX_RANGE_DAYS . ' يوماً بالطلب الواحد.')
                ->danger()
                ->send();
            return;
        }

        $url      = AppSetting::get('deals_api_url');
        $username = AppSetting::get('deals_api_username');
        $password = AppSetting::get('deals_api_password');

        if (! $url || ! $username) {
            Notification::make()->title('إعدادات API خطوط الوكلاء غير مكتملة')->danger()->send();
            return;
        }

        $campaignStart = Carbon::parse(AppSetting::get('deals_campaign_start_date', '2026-05-17'))->startOfDay();

        $this->campaignStartLabel = $campaignStart->format('Y-m-d');

        $preCampaign  = $this->fetchCumulativeCount($url, $username, $password, $agent->agent_id, self::LIFETIME_ANCHOR_DATE, $campaignStart->format('Y-m-d'));
        $postCampaign = $this->fetchCumulativeCount($url, $username, $password, $agent->agent_id, $campaignStart->format('Y-m-d'), today()->format('Y-m-d'));

        $this->preCampaignLineCount  = $preCampaign;
        $this->postCampaignLineCount = $postCampaign;
        $this->preCampaignFailed     = $preCampaign === null;
        $this->postCampaignFailed    = $postCampaign === null;

        // يوم الأساس اللازم لحساب الفرق اليومي لأول يوم بالفترة
        $baselineDay = $from->copy()->subDay();

        $queryDays = collect(CarbonPeriod::create($from, $until))
            ->map(fn ($d) => Carbon::parse($d)->format('Y-m-d'))
            ->filter(fn ($day) => Carbon::parse($day)->gte($campaignStart))
            ->values();

        if ($baselineDay->gte($campaignStart)) {
            $queryDays->prepend($baselineDay->format('Y-m-d'));
        }

        $queryDays = $queryDays->unique()->values();

        $cumulativeNew       = [];
        $cumulativeTransfer  = [];
        $failedDays          = [];

        foreach ($queryDays->chunk(20) as $batch) {
            $responses = Http::pool(function (Pool $pool) use ($batch, $url, $username, $password, $agent, $campaignStart) {
                return $batch->map(fn ($day) =>
                    $pool->as($day)
                         ->withoutVerifying()
                         ->timeout(30)
                         ->post($url, [
                             'username'  => $username,
                             'password'  => $password,
                             'apiName'   => 'GetSubCustomerDeals',
                             'wildcards' => [$agent->agent_id, $campaignStart->format('Y-m-d'), $day],
                         ])
                )->all();
            });

            foreach ($batch as $day) {
                $response = $responses[$day] ?? null;

                if (! $response || $response instanceof \Exception) {
                    $failedDays[] = $day;
                    continue;
                }

                $body = $response->json();

                if (($body['result'] ?? '') !== 'SUCCESS') {
                    $failedDays[] = $day;
                    continue;
                }

                $apiRows = collect($body['data'] ?? []);

                $cumulativeNew[$day]      = (int) $apiRows->where('task_name', 'new-order')->where('status', 'Activated')->sum(fn ($r) => (int) $r['count']);
                $cumulativeTransfer[$day] = (int) $apiRows->where('task_name', 'number-portability')->where('status', 'Activated')->sum(fn ($r) => (int) $r['count']);
            }
        }

        // أي يوم قبل بداية الحملة يُعتبر تراكمياً صفراً دون الحاجة لاستعلامه من الـ API
        $getCumulative = function (string $day) use ($campaignStart, $cumulativeNew, $cumulativeTransfer): ?array {
            if (Carbon::parse($day)->lt($campaignStart)) {
                return ['new' => 0, 'transfer' => 0];
            }

            if (isset($cumulativeNew[$day], $cumulativeTransfer[$day])) {
                return ['new' => $cumulativeNew[$day], 'transfer' => $cumulativeTransfer[$day]];
            }

            return null;
        };

        $rows = [];
        $incompleteCount = 0;

        foreach (CarbonPeriod::create($from, $until) as $d) {
            $day     = Carbon::parse($d)->format('Y-m-d');
            $prevDay = Carbon::parse($d)->subDay()->format('Y-m-d');

            if (Carbon::parse($day)->lt($campaignStart)) {
                $rows[] = [
                    'date'            => $day,
                    'daily_new'       => 0,
                    'daily_transfer'  => 0,
                    'daily_total'     => 0,
                    'cumulative_total'=> 0,
                    'status'          => 'قبل بداية الحملة',
                    'ok'              => true,
                ];
                continue;
            }

            $current  = $getCumulative($day);
            $previous = $getCumulative($prevDay);

            if (! $current || ! $previous) {
                $incompleteCount++;
                $rows[] = [
                    'date'            => $day,
                    'daily_new'       => null,
                    'daily_transfer'  => null,
                    'daily_total'     => null,
                    'cumulative_total'=> $current ? ($current['new'] + $current['transfer']) : null,
                    'status'          => 'غير مكتمل',
                    'ok'              => false,
                ];
                continue;
            }

            $dailyNew      = $current['new'] - $previous['new'];
            $dailyTransfer = $current['transfer'] - $previous['transfer'];

            $rows[] = [
                'date'            => $day,
                'daily_new'       => $dailyNew,
                'daily_transfer'  => $dailyTransfer,
                'daily_total'     => $dailyNew + $dailyTransfer,
                'cumulative_total'=> $current['new'] + $current['transfer'],
                'status'          => 'تم',
                'ok'              => true,
            ];
        }

        $this->reportRows          = $rows;
        $this->reportAgentLabel    = $agent->phone ? "{$agent->agent_name} — {$agent->phone}" : $agent->agent_name;
        $this->reportAgentId       = $agent->agent_id;
        $this->reportFrom          = $from->format('Y-m-d');
        $this->reportUntil         = $until->format('Y-m-d');
        $this->incompleteDaysCount = $incompleteCount;

        if ($incompleteCount > 0) {
            Notification::make()
                ->title('تم الجلب مع وجود أيام غير مكتملة')
                ->body($incompleteCount . ' يوم لم يكتمل بسبب فشل الاتصال بالـ API.')
                ->warning()
                ->send();
        } else {
            Notification::make()->title('تم جلب التقرير بنجاح')->success()->send();
        }
    }

    public function exportPdf(): ?StreamedResponse
    {
        if (! $this->reportAgentId || count($this->reportRows) === 0) {
            Notification::make()->title('يرجى جلب التقرير أولاً')->danger()->send();
            return null;
        }

        $agent = Agent::find($this->reportAgentId);

        if (! $agent) {
            Notification::make()->title('تعذر العثور على الوكيل')->danger()->send();
            return null;
        }

        return app(AgentDealsReportPdf::class)->download($agent, [
            'rows'                  => $this->reportRows,
            'periodFrom'            => $this->reportFrom,
            'periodUntil'           => $this->reportUntil,
            'campaignStartLabel'    => $this->campaignStartLabel,
            'preCampaignLineCount'  => $this->preCampaignLineCount,
            'preCampaignFailed'     => $this->preCampaignFailed,
            'postCampaignLineCount' => $this->postCampaignLineCount,
            'postCampaignFailed'    => $this->postCampaignFailed,
            'incompleteDaysCount'   => $this->incompleteDaysCount,
            'generatedAt'           => now()->format('Y-m-d H:i'),
            'generatedBy'           => auth()->user()?->name,
        ]);
    }

    private function fetchCumulativeCount(string $url, string $username, ?string $password, string $agentId, string $from, string $to): ?int
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->post($url, [
                    'username'  => $username,
                    'password'  => $password,
                    'apiName'   => 'GetSubCustomerDeals',
                    'wildcards' => [$agentId, $from, $to],
                ]);
        } catch (\Exception) {
            return null;
        }

        $body = $response->json();

        if (($body['result'] ?? '') !== 'SUCCESS') {
            return null;
        }

        $rows = collect($body['data'] ?? []);

        $newLines  = (int) $rows->where('task_name', 'new-order')->where('status', 'Activated')->sum(fn ($r) => (int) $r['count']);
        $transfers = (int) $rows->where('task_name', 'number-portability')->where('status', 'Activated')->sum(fn ($r) => (int) $r['count']);

        return $newLines + $transfers;
    }
}
