<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\ClubChangeRequest;
use App\Models\HistoryLog;
use Filament\Widgets\Widget;

class TodayActivityWidget extends Widget
{
    protected static ?int $sort = 10;

    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.today-activity-widget';

    protected function getViewData(): array
    {
        $today = now()->startOfDay();

        $promotions   = HistoryLog::where('event_type', 'promotion')->where('event_timestamp', '>=', $today)->count();
        $demotions    = HistoryLog::where('event_type', 'demotion')->where('event_timestamp', '>=', $today)->count();
        $firstArrivals = Agent::where('is_first_arrival', true)->where('created_at', '>=', $today)->count();
        $pendingToday = ClubChangeRequest::whereDate('created_at', today())->where('status', 'pending')->count();

        return compact('promotions', 'demotions', 'firstArrivals', 'pendingToday');
    }
}
