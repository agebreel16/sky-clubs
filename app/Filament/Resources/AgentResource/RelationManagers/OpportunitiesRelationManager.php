<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OpportunitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunities';

    protected static ?string $title = 'فرص السحب';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('opportunity_id')
            ->columns([
                TextColumn::make('type')
                    ->label('نوع الفرصة')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'entry')         { return 'success'; }
                        if ($state === 'maintenance')   { return 'info'; }
                        if ($state === 'bonus')         { return 'warning'; }
                        if ($state === 'first_arrival') { return 'danger'; }
                        return 'gray';
                    })
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'entry')         { return 'دخول'; }
                        if ($state === 'maintenance')   { return 'صيانة'; }
                        if ($state === 'bonus')         { return 'مكافأة'; }
                        if ($state === 'first_arrival') { return 'أوائل'; }
                        return $state;
                    }),

                TextColumn::make('club.club_name')
                    ->label('النادي المستحق'),

                TextColumn::make('earned_date')
                    ->label('تاريخ الاستحقاق')
                    ->dateTime('d/m/Y'),

                IconColumn::make('is_active')
                    ->label('فعالة')
                    ->boolean(),
            ])
            ->defaultSort('earned_date', 'desc')
            ->actions([]);
    }
}
