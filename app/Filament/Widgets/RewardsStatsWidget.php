<?php

namespace App\Filament\Widgets;

use App\Models\Reward;
use Filament\Widgets\Widget;

class RewardsStatsWidget extends Widget
{
    protected static ?int $sort = 6;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.rewards-stats-widget';

    protected function getViewData(): array
    {
        $pendingAmount = Reward::where('payment_status', 'pending')->sum('amount');
        $pendingCount  = Reward::where('payment_status', 'pending')->count();
        $paidAmount    = Reward::where('payment_status', 'paid')->sum('amount');
        $monthAmount   = Reward::where('created_at', '>=', now()->startOfMonth())->sum('amount');
        $monthCount    = Reward::where('created_at', '>=', now()->startOfMonth())->count();

        return compact('pendingAmount', 'pendingCount', 'paidAmount', 'monthAmount', 'monthCount');
    }
}
