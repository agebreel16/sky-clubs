<?php

namespace App\Filament\Widgets;

use App\Models\ClubChangeRequest;
use App\Models\DataImport;
use App\Models\Reward;
use Filament\Widgets\Widget;

class PriorityAlertsWidget extends Widget
{
    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.priority-alerts-widget';

    public static function canView(): bool
    {
        return ClubChangeRequest::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(3))
                ->exists()
            || DataImport::where('status', 'failed')
                ->where('created_at', '>=', now()->subHours(48))
                ->exists()
            || Reward::where('payment_status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->exists();
    }

    protected function getViewData(): array
    {
        $staleRequests = ClubChangeRequest::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(3))
            ->count();

        $failedImports = DataImport::where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(48))
            ->count();

        $staleRewards = Reward::where('payment_status', 'pending')
            ->where('created_at', '<', now()->subDays(7))
            ->count();

        $alerts = [];

        if ($staleRequests > 0) {
            $alerts[] = [
                'severity' => 'danger',
                'icon'     => 'clock',
                'message'  => "يوجد {$staleRequests} طلب تغيير نادٍ معلّق منذ أكثر من 3 أيام",
                'url'      => '/admin/club-change-requests',
                'cta'      => 'مراجعة الطلبات',
            ];
        }

        if ($failedImports > 0) {
            $alerts[] = [
                'severity' => 'danger',
                'icon'     => 'x-circle',
                'message'  => "يوجد {$failedImports} استيراد فاشل خلال آخر 48 ساعة",
                'url'      => '/admin/data-imports',
                'cta'      => 'عرض الاستيرادات',
            ];
        }

        if ($staleRewards > 0) {
            $alerts[] = [
                'severity' => 'warning',
                'icon'     => 'currency',
                'message'  => "يوجد {$staleRewards} مكافأة معلّقة الدفع منذ أكثر من 7 أيام",
                'url'      => '/admin/rewards',
                'cta'      => 'عرض المكافآت',
            ];
        }

        return ['alerts' => $alerts];
    }
}
