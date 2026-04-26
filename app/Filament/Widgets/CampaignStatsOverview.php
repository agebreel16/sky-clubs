<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class CampaignStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'نظرة عامة على الحملة';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalAgents    = Agent::count();
        $agentsInClubs  = Agent::whereNotNull('current_club_id')->count();
        $agentsOutClubs = $totalAgents - $agentsInClubs;

        $campaignStart = Carbon::create(2026, 5, 1);
        $campaignEnd   = Carbon::create(2027, 4, 30);
        $today         = now();

        $daysRemaining  = max(0, (int) $today->diffInDays($campaignEnd, false));
        $totalDays      = (int) $campaignStart->diffInDays($campaignEnd);
        $daysPassed     = (int) $campaignStart->diffInDays($today, false);
        $daysPassed     = max(0, min($daysPassed, $totalDays));
        $progress       = $totalDays > 0 ? round(($daysPassed / $totalDays) * 100) : 0;

        return [
            Stat::make('إجمالي الوكلاء', number_format($totalAgents))
                ->description('إجمالي المسجلين في الحملة')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('في الأندية', number_format($agentsInClubs))
                ->description("{$agentsOutClubs} خارج الأندية حالياً")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success')
                ->chart([2, 4, 3, 6, 4, 5, 4, 7]),

            Stat::make('أيام متبقية', number_format($daysRemaining))
                ->description('حتى انتهاء موسم 2026-2027')
                ->descriptionIcon('heroicon-m-clock')
                ->color($daysRemaining < 30 ? 'danger' : ($daysRemaining < 90 ? 'warning' : 'primary')),

            Stat::make('نسبة الإنجاز الزمنية', "{$progress}%")
                ->description("مضى {$daysPassed} يوم")
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('gray'),
        ];
    }
}
