<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use App\Models\Club;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-users'; }

    public static function getNavigationGroup(): string { return 'إدارة الحملة'; }

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'وكيل';

    protected static ?string $pluralLabel = 'الوكلاء';

    protected static ?string $recordTitleAttribute = 'agent_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('هوية الوكيل')
                ->schema([
                    TextInput::make('agent_name')
                        ->label('اسم الوكيل')
                        ->required()
                        ->maxLength(200)
                        ->placeholder('أدخل الاسم الكامل'),
                ]),

            Section::make('بيانات الأرقام')
                ->columns(3)
                ->description('الأساس مجمّد منذ بداية الحملة ولا يمكن تعديله.')
                ->schema([
                    TextInput::make('baseline_count')
                        ->label('الأساس (مجمّد)')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->disabledOn('edit')
                        ->dehydratedWhenHidden(),

                    TextInput::make('pre_campaign_count')
                        ->label('الأرقام القديمة')
                        ->required()
                        ->integer()
                        ->minValue(0),

                    TextInput::make('current_total')
                        ->label('الإجمالي الحالي')
                        ->required()
                        ->integer()
                        ->minValue(0),

                    TextInput::make('transfer_count')
                        ->label('التحويلات')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->default(0),

                    TextInput::make('new_line_count')
                        ->label('الخطوط الجديدة')
                        ->required()
                        ->integer()
                        ->minValue(0)
                        ->default(0),
                ]),

            Section::make('عضوية النادي')
                ->columns(2)
                ->schema([
                    Select::make('current_club_id')
                        ->label('النادي الحالي')
                        ->options(Club::all()->pluck('club_name', 'club_id'))
                        ->nullable()
                        ->placeholder('خارج الأندية'),

                    DateTimePicker::make('entry_date')
                        ->label('تاريخ الدخول للنادي')
                        ->nullable(),

                    DateTimePicker::make('demotion_timer_start')
                        ->label('بداية عداد التهبيط')
                        ->nullable(),

                    Toggle::make('is_first_arrival')
                        ->label('من الأوائل'),
                ]),

            Section::make('ملاحظات')
                ->schema([
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->nullable()
                        ->rows(4)
                        ->maxLength(1000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent_name')
                    ->label('اسم الوكيل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('club.club_name')
                    ->label('النادي الحالي')
                    ->badge()
                    ->color(function ($record) {
                        $order = $record->club ? (int) $record->club->club_order : 0;
                        if ($order === 1) { return 'success'; }
                        if ($order === 2) { return 'info'; }
                        if ($order === 3) { return 'warning'; }
                        return 'gray';
                    })
                    ->default('خارج الأندية'),

                TextColumn::make('current_total')
                    ->label('إجمالي الأرقام')
                    ->sortable(),

                TextColumn::make('campaign_increase_display')
                    ->label('الزيادة')
                    ->getStateUsing(function (Agent $record): int {
                        return $record->current_total - $record->pre_campaign_count;
                    })
                    ->badge()
                    ->color('success')
                    ->sortable(false),

                TextColumn::make('transfer_percentage_display')
                    ->label('نسبة التحويل %')
                    ->getStateUsing(function (Agent $record): string {
                        if (!$record->club) {
                            return '—';
                        }
                        $required = (int) $record->club->required_increase;
                        if ($required === 0) {
                            return '0%';
                        }
                        $pct = round(($record->transfer_count / $required) * 100, 1);
                        return "{$pct}%";
                    })
                    ->badge()
                    ->color(function (Agent $record): string {
                        if (!$record->club) {
                            return 'gray';
                        }
                        $required = (int) $record->club->required_increase;
                        if ($required === 0) {
                            return 'gray';
                        }
                        $pct = ($record->transfer_count / $required) * 100;
                        return $pct >= 60 ? 'success' : 'danger';
                    })
                    ->sortable(false),

                IconColumn::make('is_first_arrival')
                    ->label('أوائل')
                    ->boolean(),

                TextColumn::make('demotion_countdown')
                    ->label('عداد')
                    ->getStateUsing(function (Agent $record): string {
                        if (!$record->demotion_timer_start || !$record->club) {
                            return '—';
                        }
                        $deadline = $record->demotion_timer_start->copy()->addDays($record->club->demotion_timer_days);
                        $days = max(0, (int) now()->diffInDays($deadline, false));
                        return $days . ' يوم';
                    })
                    ->color(function (Agent $record): string {
                        if (!$record->demotion_timer_start || !$record->club) {
                            return 'gray';
                        }
                        $deadline = $record->demotion_timer_start->copy()->addDays($record->club->demotion_timer_days);
                        $days = (int) now()->diffInDays($deadline, false);
                        if ($days <= 1) { return 'danger'; }
                        if ($days <= 3) { return 'warning'; }
                        return 'info';
                    })
                    ->sortable(false),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('بحث بالاسم...')
            ->filters([
                SelectFilter::make('current_club_id')
                    ->label('النادي')
                    ->options(Club::all()->pluck('club_name', 'club_id'))
                    ->placeholder('كل الأندية'),

                TernaryFilter::make('is_first_arrival')
                    ->label('من الأوائل'),

                TernaryFilter::make('has_demotion_timer')
                    ->label('عداد نشط')
                    ->attribute('demotion_timer_start')
                    ->queries(
                        fn ($query) => $query->whereNotNull('demotion_timer_start'),
                        fn ($query) => $query->whereNull('demotion_timer_start'),
                    ),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف المحدد'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\AgentResource\RelationManagers\HistoryLogsRelationManager::class,
            \App\Filament\Resources\AgentResource\RelationManagers\OpportunitiesRelationManager::class,
            \App\Filament\Resources\AgentResource\RelationManagers\RewardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'           => Pages\ListAgents::route('/'),
            'create'          => Pages\CreateAgent::route('/create'),
            'demotion-report' => Pages\DemotionReport::route('/demotion-report'),
            'view'            => Pages\ViewAgent::route('/{record}'),
            'edit'            => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
