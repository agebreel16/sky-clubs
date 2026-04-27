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
                    ->label('الوكيل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('الحدث')
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
                    ->label('من النادي')
                    ->default('—'),
                TextColumn::make('toClub.club_name')
                    ->label('إلى النادي')
                    ->default('—'),
                TextColumn::make('reason')
                    ->label('السبب')
                    ->limit(60),
                TextColumn::make('event_timestamp')
                    ->label('وقت الحدث')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تم التسجيل')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('event_timestamp', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('نوع الحدث')
                    ->options([
                        'promotion'   => 'ترقية',
                        'demotion'    => 'تهبيط',
                        'warning'     => 'تحذير',
                        'achievement' => 'الإنجاز',
                        'data_import' => 'استيراد البيانات',
                    ]),
                SelectFilter::make('from_club_id')
                    ->label('من النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id')),
                SelectFilter::make('to_club_id')
                    ->label('إلى النادي')
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
