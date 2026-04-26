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

            // Premium Metadata
            $metadata = match($club->club_name) {
                'Launch Club'     => [
                    'icon'       => 'heroicon-o-rocket-launch',
                    'gradient'   => 'from-blue-600 to-sky-400',
                    'shadow'     => 'shadow-blue-500/20',
                    'text_color' => 'text-blue-600',
                    'bg_light'   => 'bg-blue-50',
                ],
                'Excellence Club' => [
                    'icon'       => 'heroicon-o-sparkles',
                    'gradient'   => 'from-amber-600 to-yellow-400',
                    'shadow'     => 'shadow-amber-500/20',
                    'text_color' => 'text-amber-600',
                    'bg_light'   => 'bg-amber-50',
                ],
                'Peak Club'       => [
                    'icon'       => 'heroicon-o-trophy',
                    'gradient'   => 'from-indigo-700 to-violet-500',
                    'shadow'     => 'shadow-indigo-500/20',
                    'text_color' => 'text-indigo-600',
                    'bg_light'   => 'bg-indigo-50',
                ],
                default           => [
                    'icon'       => 'heroicon-o-star',
                    'gradient'   => 'from-gray-600 to-gray-400',
                    'shadow'     => 'shadow-gray-500/20',
                    'text_color' => 'text-gray-600',
                    'bg_light'   => 'bg-gray-50',
                ],
            };

            return [
                'club'          => $club,
                'membersCount'  => $membersCount,
                'percentage'    => $percentage,
                'status'        => $status,
                'latestMember'  => $latestMember,
                'metadata'      => $metadata,
            ];
        });

        return ['clubs' => $clubs];
    }
}
