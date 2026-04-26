<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RewardsRelationManager extends RelationManager
{
    protected static string $relationship = 'rewards';

    protected static ?string $title = 'الجوائز والمكافآت';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reward_id')
            ->columns([
                TextColumn::make('club.club_name')
                    ->label('النادي'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('ILS'),

                TextColumn::make('payment_status')
                    ->label('الحالة')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'paid')    { return 'success'; }
                        if ($state === 'pending') { return 'warning'; }
                        if ($state === 'failed')  { return 'danger'; }
                        return 'gray';
                    }),

                TextColumn::make('paid_date')
                    ->label('تاريخ الصرف')
                    ->dateTime('d/m/Y'),

                IconColumn::make('is_first_arrival')
                    ->label('أوائل الوصول')
                    ->boolean(),
            ])
            ->actions([
                Action::make('mark_as_paid')
                    ->label('تأكيد الصرف')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->payment_status === 'paid')
                    ->action(fn ($record) => $record->update([
                        'payment_status' => 'paid',
                        'paid_date'      => now(),
                    ])),
            ]);
    }
}
