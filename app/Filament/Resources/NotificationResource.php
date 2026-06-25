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

    public static function getNavigationGroup(): ?string { return 'السجلات'; }

    protected static ?int $navigationSort = 2;

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
                    ->label('الوكيل')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('notification_type')
                    ->label('النوع')
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
                    ->label('العنوان')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('category')
                    ->badge(),
                TextColumn::make('stage')
                    ->default('—'),
                TextColumn::make('club.club_name')
                    ->label('النادي')
                    ->default('—'),
                IconColumn::make('is_read')
                    ->label('مقروء')
                    ->boolean(),
                TextColumn::make('sent_at')
                    ->label('تم الإرسال')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                SelectFilter::make('notification_type')
                    ->label('النوع')
                    ->options([
                        'milestone'   => 'معلم',
                        'progress'    => 'التقدم',
                        'achievement' => 'الإنجاز',
                        'promotion'   => 'ترقية',
                        'demotion'    => 'تهبيط',
                        'warning'     => 'تحذير',
                    ]),
                SelectFilter::make('category')
                    ->options([
                        'outside_clubs' => 'خارج الأندية',
                        'in_club'       => 'في النادي',
                    ]),
                TernaryFilter::make('is_read')->label('حالة القراءة'),
                SelectFilter::make('club_id')
                    ->label('النادي')
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
