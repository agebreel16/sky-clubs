<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\Club;
use App\Models\ClubChangeRequest;
use Filament\Widgets\Widget;

class CampaignFunnelWidget extends Widget
{
    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.campaign-funnel-widget';

    protected function getViewData(): array
    {
        $clubs = Club::orderBy('club_order')->get()->keyBy('club_order');
        $club1 = $clubs->get(1);

        $threshold = $club1?->required_increase ?? 25;

        // Agents with pending promotion requests (any club level)
        $pendingAgentIds = ClubChangeRequest::where('status', 'pending')
            ->where('change_type', 'promotion')
            ->pluck('agent_id')
            ->unique();

        $pendingCount = $pendingAgentIds->count();

        // Outside agents (not in any club, not violators)
        $outsideQuery = Agent::whereNull('current_club_id')
            ->where('is_violator', false)
            ->whereNotIn('agent_id', $pendingAgentIds);

        $notStarted = (clone $outsideQuery)
            ->where('transfer_count', '=', 0)
            ->count();

        $inProgress = (clone $outsideQuery)
            ->whereBetween('transfer_count', [1, 9])
            ->count();

        $nearDoor = (clone $outsideQuery)
            ->where('transfer_count', '>=', 10)
            ->count();

        // Agents inside clubs (non-violators)
        $clubCounts = [];
        foreach ($clubs as $order => $club) {
            $clubCounts[$order] = Agent::where('current_club_id', $club->club_id)
                ->where('is_violator', false)
                ->count();
        }

        $violatorsCount = Agent::where('is_violator', true)->count();

        $total = Agent::count();

        $stages = [
            [
                'label'   => 'لم يبدأ بعد',
                'count'   => $notStarted,
                'color'   => 'var(--sc-text3)',
                'bg'      => 'var(--sc-surface2)',
                'icon'    => 'minus',
                'url'     => '/admin/agent-filter/not_started',
            ],
            [
                'label'   => 'في الطريق',
                'count'   => $inProgress,
                'color'   => 'var(--sc-accent)',
                'bg'      => 'oklch(0.60 0.22 245 / 0.12)',
                'icon'    => 'trending-up',
                'url'     => '/admin/agent-filter/in_progress',
            ],
            [
                'label'   => 'على الأعتاب',
                'count'   => $nearDoor,
                'color'   => 'var(--sc-gold)',
                'bg'      => 'oklch(0.78 0.15 82 / 0.12)',
                'icon'    => 'flag',
                'url'     => '/admin/agent-filter/near_door',
            ],
            [
                'label'   => 'منتظر قبول',
                'count'   => $pendingCount,
                'color'   => 'var(--sc-orange)',
                'bg'      => 'oklch(0.72 0.18 55 / 0.12)',
                'icon'    => 'clock',
                'url'     => '/admin/club-change-requests',
            ],
        ];

        foreach ($clubs->sortBy('club_order') as $order => $club) {
            $colors = match ((int) $order) {
                1 => ['var(--sc-accent)', 'oklch(0.60 0.22 245 / 0.15)'],
                2 => ['var(--sc-gold)',   'oklch(0.78 0.15 82 / 0.15)'],
                3 => ['var(--sc-purple)', 'oklch(0.62 0.20 295 / 0.15)'],
                default => ['var(--sc-text2)', 'var(--sc-surface2)'],
            };

            $stages[] = [
                'label' => $club->club_name,
                'count' => $clubCounts[$order] ?? 0,
                'color' => $colors[0],
                'bg'    => $colors[1],
                'icon'  => 'star',
                'url'   => '/admin/agents?tableFilters[current_club_id][value]=' . $club->club_id,
            ];
        }

        $stages[] = [
            'label' => 'مخالفون',
            'count' => $violatorsCount,
            'color' => 'var(--sc-red)',
            'bg'    => 'oklch(0.65 0.22 25 / 0.10)',
            'icon'  => 'ban',
            'url'   => '/admin/agents?tableFilters[is_violator][value]=1',
        ];

        return [
            'stages'    => $stages,
            'total'     => $total,
            'threshold' => $threshold,
        ];
    }
}
