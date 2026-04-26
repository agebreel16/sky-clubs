<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Models\Club;
use Filament\Widgets\Widget;

class ClubStatusWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.club-status-widget';

    protected function getViewData(): array
    {
        $clubs = Club::orderBy('club_order')->get()->map(function (Club $club) {
            $membersCount = Agent::where('current_club_id', $club->club_id)->count();
            $percentage   = $club->seat_capacity > 0
                ? round(($membersCount / $club->seat_capacity) * 100)
                : 0;

            $status = $membersCount >= $club->seat_capacity ? 'EXCEEDING' : 'MEETING';

            $latestMember = Agent::where('current_club_id', $club->club_id)
                ->orderByDesc('entry_date')
                ->first();

            return [
                'club'          => $club,
                'membersCount'  => $membersCount,
                'percentage'    => $percentage,
                'status'        => $status,
                'latestMember'  => $latestMember,
            ];
        });

        return ['clubs' => $clubs];
    }
}
