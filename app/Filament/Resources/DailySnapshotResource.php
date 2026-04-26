<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailySnapshotResource\Pages;
use App\Models\Club;
use App\Models\DailySnapshot;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;

class DailySnapshotResource extends Resource
{
    protected static ?string $model = DailySnapshot::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-camera'; }

    public static function getNavigationGroup(): ?string { return 'إدارة البيانات'; }

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'لقطة يومية';

    protected static ?string $pluralLabel = 'اللقطات اليومية';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('data_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('agent.agent_name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('club.club_name')
                    ->label('Club on Date')
                    ->badge()
                    ->default('No Club'),
                TextColumn::make('baseline_count')
                    ->label('Baseline')
                    ->sortable(),
                TextColumn::make('pre_campaign_count')
                    ->label('Pre-Campaign')
                    ->sortable(),
                TextColumn::make('current_total')
                    ->label('Total Lines')
                    ->sortable(),
                TextColumn::make('transfer_count')
                    ->label('Transfers')
                    ->sortable(),
                TextColumn::make('new_line_count')
                    ->label('New Lines')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Captured')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data_date', 'desc')
            ->filters([
                SelectFilter::make('club_id_at_date')
                    ->label('Club on Date')
                    ->options(Club::all()->pluck('club_name', 'club_id')),
                Filter::make('data_date')
                    ->form([
                        DatePicker::make('from')->label('From Date'),
                        DatePicker::make('until')->label('Until Date'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $d) => $q->whereDate('data_date', '>=', $d))
                            ->when($data['until'], fn ($q, $d) => $q->whereDate('data_date', '<=', $d));
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailySnapshots::route('/'),
            'view'  => Pages\ViewDailySnapshot::route('/{record}'),
        ];
    }
}
