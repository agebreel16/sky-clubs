<?php

namespace App\Filament\Widgets;

use App\Models\Agent;
use App\Filament\Resources\AgentResource;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AtRiskAgentsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'الوكلاء في خطر التهبيط';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Agent::query()
                    ->whereNotNull('demotion_timer_start')
                    ->whereNotNull('current_club_id')
                    ->whereHas('club')
                    ->orderByRaw('DATE_ADD(demotion_timer_start, INTERVAL (SELECT demotion_timer_days FROM clubs WHERE clubs.club_id = agents.current_club_id) DAY) ASC')
            )
            ->columns([
                TextColumn::make('agent_name')
                    ->label('اسم الوكيل')
                    ->searchable()
                    ->url(fn (Agent $record): string => AgentResource::getUrl('view', ['record' => $record]))
                    ->color('primary')
                    ->weight('bold'),

                TextColumn::make('club.club_name')
                    ->label('النادي')
                    ->badge()
                    ->color(function ($record): string {
                        $order = $record->club ? (int) $record->club->club_order : 0;
                        if ($order === 1) { return 'success'; }
                        if ($order === 2) { return 'info'; }
                        if ($order === 3) { return 'warning'; }
                        return 'gray';
                    }),

                TextColumn::make('days_left')
                    ->label('أيام متبقية')
                    ->getStateUsing(function (Agent $record): int {
                        if (!$record->club || !$record->demotion_timer_start) {
                            return 0;
                        }
                        $deadline = $record->demotion_timer_start->copy()->addDays($record->club->demotion_timer_days);
                        return max(0, (int) now()->diffInDays($deadline, false));
                    })
                    ->badge()
                    ->color(function (Agent $record): string {
                        if (!$record->club || !$record->demotion_timer_start) {
                            return 'gray';
                        }
                        $deadline = $record->demotion_timer_start->copy()->addDays($record->club->demotion_timer_days);
                        $left = (int) now()->diffInDays($deadline, false);
                        if ($left <= 1) { return 'danger'; }
                        if ($left <= 3) { return 'warning'; }
                        return 'info';
                    })
                    ->sortable(false),

                TextColumn::make('lines_needed')
                    ->label('أرقام مطلوبة')
                    ->getStateUsing(function (Agent $record): int {
                        if (!$record->club) {
                            return 0;
                        }
                        $required = (int) $record->club->required_increase;
                        $increase = $record->current_total - $record->pre_campaign_count;
                        return max(0, $required - $increase);
                    })
                    ->badge()
                    ->color('danger'),
            ])
            ->actions([
                Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Agent $record): string => AgentResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('لا يوجد وكلاء في خطر التهبيط حالياً')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->paginated(false);
    }
}
