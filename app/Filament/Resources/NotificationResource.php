<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\AgentNotification;
use App\Models\Club;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NotificationResource extends Resource
{
    protected static ?string $model = AgentNotification::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-bell'; }

    public static function getNavigationGroup(): ?string { return 'إدارة البيانات'; }

    protected static ?int $navigationSort = 4;

    protected static ?string $label = 'إشعار';

    protected static ?string $pluralLabel = 'الإشعارات';

    protected static ?string $slug = 'agent-notifications';

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
                TextColumn::make('notification_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'promotion'   => 'success',
                        'demotion'    => 'danger',
                        'warning'     => 'warning',
                        'achievement' => 'info',
                        'milestone'   => 'primary',
                        'progress'    => 'gray',
                        default       => 'gray',
                    }),
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('stage')
                    ->default('—'),
                TextColumn::make('club.club_name')
                    ->label('Club')
                    ->default('—'),
                IconColumn::make('is_read')
                    ->label('Read')
                    ->boolean(),
                TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                SelectFilter::make('notification_type')
                    ->label('Type')
                    ->options([
                        'milestone'   => 'Milestone',
                        'progress'    => 'Progress',
                        'achievement' => 'Achievement',
                        'promotion'   => 'Promotion',
                        'demotion'    => 'Demotion',
                        'warning'     => 'Warning',
                    ]),
                SelectFilter::make('category')
                    ->options([
                        'outside_clubs' => 'Outside Clubs',
                        'in_club'       => 'In Club',
                    ]),
                TernaryFilter::make('is_read')->label('Read Status'),
                SelectFilter::make('club_id')
                    ->label('Club')
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
            'index' => Pages\ListNotifications::route('/'),
            'view'  => Pages\ViewNotification::route('/{record}'),
        ];
    }
}
