<?php

namespace App\Livewire\AgentPortal;

class AgentProgress extends AgentPortalPage
{
    public array  $weeks          = [];
    public int    $thisMonthTotal = 0;
    public int    $prevMonthTotal = 0;
    public string $thisMonthLabel = '';
    public string $prevMonthLabel = '';

    public function mount(string $uuid): void
    {
        parent::mount($uuid);
        $this->buildWeeklyData();
    }

    private function buildWeeklyData(): void
    {
        $snapshots = $this->agent->dailySnapshots()
            ->orderBy('data_date')
            ->get(['data_date', 'transfer_count', 'new_line_count']);

        // transfer_count و new_line_count تراكميان من بداية الحملة
        // الزيادة الفعلية = آخر قيمة في الفترة - آخر قيمة قبل بداية الفترة
        $getValue = fn($snap) => $snap ? $snap->transfer_count + $snap->new_line_count : 0;

        $weeks = [];
        for ($i = 0; $i < 4; $i++) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end   = $i === 0 ? now()->endOfDay() : now()->subWeeks($i)->endOfWeek();

            $lastInPeriod = $snapshots->filter(fn($s) => $s->data_date->between($start, $end))->last();
            $beforePeriod = $snapshots->filter(fn($s) => $s->data_date->lt($start))->last();

            $total     = max(0, $getValue($lastInPeriod) - $getValue($beforePeriod));
            $endLabel  = $i === 0 ? now() : $end;

            $weeks[] = [
                'label'      => match ($i) {
                    0       => 'الأسبوع الحالي',
                    1       => 'الأسبوع الماضي',
                    default => 'قبل ' . $i . ' أسابيع',
                },
                'date_range' => $start->translatedFormat('d M') . ' – ' . $endLabel->translatedFormat('d M'),
                'total'      => $total,
                'is_current' => $i === 0,
            ];
        }

        $weeks    = array_reverse($weeks);
        $maxTotal = max(array_column($weeks, 'total')) ?: 1;
        foreach ($weeks as &$w) {
            $w['pct'] = (int) round(($w['total'] / $maxTotal) * 100);
        }
        unset($w);

        $this->weeks = $weeks;

        $monthStart  = now()->startOfMonth();
        $prevStart   = now()->subMonth()->startOfMonth();
        $prevEnd     = now()->subMonth()->endOfMonth();

        $lastThisMonth   = $snapshots->filter(fn($s) => $s->data_date->isCurrentMonth())->last();
        $beforeThisMonth = $snapshots->filter(fn($s) => $s->data_date->lt($monthStart))->last();
        $this->thisMonthTotal = max(0, $getValue($lastThisMonth) - $getValue($beforeThisMonth));

        $lastPrevMonth   = $snapshots->filter(fn($s) => $s->data_date->between($prevStart, $prevEnd))->last();
        $beforePrevMonth = $snapshots->filter(fn($s) => $s->data_date->lt($prevStart))->last();
        $this->prevMonthTotal = max(0, $getValue($lastPrevMonth) - $getValue($beforePrevMonth));

        $this->thisMonthLabel = now()->translatedFormat('F Y');
        $this->prevMonthLabel = now()->subMonth()->translatedFormat('F Y');
    }

    public function render()
    {
        return $this->renderWithLayout('livewire.agent-portal.progress');
    }
}
