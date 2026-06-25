<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\DataImport;
use App\Models\HistoryLog;
use Filament\Widgets\Widget;

class WeeklyVelocityWidget extends Widget
{
    protected static ?int $sort = 7;

    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.weekly-velocity-widget';

    protected function getViewData(): array
    {
        $thisWeekStart = now()->startOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd   = now()->subWeek()->endOfWeek();

        $promotionsThis = HistoryLog::where('event_type', 'promotion')
            ->where('event_timestamp', '>=', $thisWeekStart)->count();
        $promotionsLast = HistoryLog::where('event_type', 'promotion')
            ->whereBetween('event_timestamp', [$lastWeekStart, $lastWeekEnd])->count();

        $newAgentsThis = Agent::where('created_at', '>=', $thisWeekStart)->count();
        $newAgentsLast = Agent::whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        $importsThis = DataImport::where('status', 'success')
            ->where('created_at', '>=', $thisWeekStart)->count();
        $importsLast = DataImport::where('status', 'success')
            ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])->count();

        $syncsThis = Agent::whereNotNull('last_self_sync_at')
            ->where('last_self_sync_at', '>=', $thisWeekStart)->count();

        $color = fn (int $cur, int $prev): string => match (true) {
            $cur > $prev => 'var(--sc-green)',
            $cur < $prev => 'var(--sc-red)',
            default      => 'var(--sc-text3)',
        };

        $diff = fn (int $cur, int $prev): string => match (true) {
            $cur > $prev => '↑ زيادة ' . ($cur - $prev) . ' عن الأسبوع الماضي',
            $cur < $prev => '↓ انخفاض ' . ($prev - $cur) . ' عن الأسبوع الماضي',
            default      => '= نفس الأسبوع الماضي',
        };

        $items = [
            [
                'label'     => 'ترقيات هذا الأسبوع',
                'value'     => $promotionsThis,
                'diff_text' => $diff($promotionsThis, $promotionsLast),
                'color'     => $color($promotionsThis, $promotionsLast),
                'icon'      => 'up',
            ],
            [
                'label'     => 'وكلاء جدد',
                'value'     => $newAgentsThis,
                'diff_text' => $diff($newAgentsThis, $newAgentsLast),
                'color'     => $color($newAgentsThis, $newAgentsLast),
                'icon'      => 'user',
            ],
            [
                'label'     => 'استيرادات ناجحة',
                'value'     => $importsThis,
                'diff_text' => $diff($importsThis, $importsLast),
                'color'     => $color($importsThis, $importsLast),
                'icon'      => 'import',
            ],
            [
                'label'     => 'مزامنات ذاتية',
                'value'     => $syncsThis,
                'diff_text' => 'زامنوا بياناتهم هذا الأسبوع',
                'color'     => 'var(--sc-purple)',
                'icon'      => 'sync',
            ],
        ];

        return ['items' => $items, 'heading' => 'نبض الحملة هذا الأسبوع'];
    }
}
