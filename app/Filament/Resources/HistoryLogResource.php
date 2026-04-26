<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HistoryLogResource\Pages;
use App\Models\Club;
use App\Models\HistoryLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class HistoryLogResource extends Resource
{
    protected static ?string $model = HistoryLog::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-clock'; }

    public static function getNavigationGroup(): ?string { return 'إدارة البيانات'; }

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'سجل الأحداث';

    protected static ?string $pluralLabel = 'سجلات الأحداث';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent.agent_name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'promotion'   => 'success',
                        'demotion'    => 'danger',
                        'warning'     => 'warning',
                        'achievement' => 'info',
                        'data_import' => 'gray',
                        default       => 'gray',
                    }),
                TextColumn::make('fromClub.club_name')
                    ->label('From Club')
                    ->default('—'),
                TextColumn::make('toClub.club_name')
                    ->label('To Club')
                    ->default('—'),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->limit(60),
                TextColumn::make('event_timestamp')
                    ->label('Event Time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Logged')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_timestamp', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event Type')
                    ->options([
                        'promotion'   => 'Promotion',
                        'demotion'    => 'Demotion',
                        'warning'     => 'Warning',
                        'achievement' => 'Achievement',
                        'data_import' => 'Data Import',
                    ]),
                SelectFilter::make('from_club_id')
                    ->label('From Club')
                    ->options(Club::all()->pluck('club_name', 'club_id')),
                SelectFilter::make('to_club_id')
                    ->label('To Club')
                    ->options(Club::all()->pluck('club_name', 'club_id')),
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
            'index' => Pages\ListHistoryLogs::route('/'),
            'view'  => Pages\ViewHistoryLog::route('/{record}'),
        ];
    }
}
