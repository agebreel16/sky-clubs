<?php

namespace App\Filament\Widgets;

use App\Models\Club;
use Filament\Widgets\Widget;

class ClubMembersChart extends Widget
{
    protected string $view = 'filament.widgets.club-members-chart';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function getClubData(): array
    {
        return Club::orderBy('club_order')
            ->withCount('agents')
            ->get()
            ->map(fn (Club $club) => [
                'name'         => $club->club_name,
                'members'      => $club->agents_count,
                'min_required' => $club->seat_capacity,
                'lottery_ready' => $club->agents_count >= $club->seat_capacity,
                'prize'        => number_format($club->grand_prize_amount, 0),
                'base_reward'  => number_format($club->base_reward_amount, 0),
                'order'        => $club->club_order,
            ])
            ->toArray();
    }
}
