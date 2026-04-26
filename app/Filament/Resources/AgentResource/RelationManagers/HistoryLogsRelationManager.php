<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HistoryLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'historyLogs';

    protected static ?string $title = 'سجل الأحداث';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('log_id')
            ->columns([
                TextColumn::make('event_timestamp')
                    ->label('التوقيت')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('event_type')
                    ->label('الحدث')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'promotion')  { return 'success'; }
                        if ($state === 'demotion')   { return 'danger'; }
                        if ($state === 'warning')    { return 'warning'; }
                        if ($state === 'achievement'){ return 'info'; }
                        return 'gray';
                    })
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'promotion')  { return 'ترقية ⬆️'; }
                        if ($state === 'demotion')   { return 'تهبيط ⬇️'; }
                        if ($state === 'warning')    { return 'تحذير ⚠️'; }
                        if ($state === 'achievement'){ return 'إنجاز ⭐'; }
                        return $state;
                    }),

                TextColumn::make('fromClub.club_name')
                    ->label('من نادي')
                    ->default('خارج الأندية'),

                TextColumn::make('toClub.club_name')
                    ->label('إلى نادي')
                    ->default('خارج الأندية'),

                TextColumn::make('reason')
                    ->label('السبب')
                    ->wrap(),
            ])
            ->defaultSort('event_timestamp', 'desc')
            ->actions([]);
    }
}
