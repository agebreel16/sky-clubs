<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\Agent;
use Filament\Resources\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables;

class DemotionReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = AgentResource::class;

    protected string $view = 'filament.resources.agent-resource.pages.demotion-report';

    protected static ?string $title = 'تقرير عداد التهبيط';

    protected static ?string $navigationLabel = 'تقرير التهبيط';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Agent::query()
                    ->whereNotNull('demotion_timer_start')
            )
            ->columns([
                Tables\Columns\TextColumn::make('agent_name')
                    ->label('الوكيل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('club.club_name')
                    ->label('النادي الحالي')
                    ->badge(),
                Tables\Columns\TextColumn::make('demotion_timer_start')
                    ->label('بداية العداد')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('الموعد النهائي')
                    ->getStateUsing(function (Agent $record) {
                        return $record->demotion_timer_start->addDays($record->club->demotion_timer_days);
                    })
                    ->dateTime(),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('العد التنازلي')
                    ->getStateUsing(function (Agent $record) {
                        $deadline = $record->demotion_timer_start->addDays($record->club->demotion_timer_days);
                        return $deadline->diffForHumans(now(), true);
                    })
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_club_id')
                    ->label('النادي')
                    ->options(\App\Models\Club::all()->pluck('club_name', 'club_id')),
            ]);
    }
}
