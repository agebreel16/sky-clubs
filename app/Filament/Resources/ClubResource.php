<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClubResource\Pages;
use App\Models\Club;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClubResource extends Resource
{
    protected static ?string $model = Club::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-star'; }

    public static function getNavigationGroup(): ?string { return 'إدارة الحملة'; }

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'نادي';

    protected static ?string $pluralLabel = 'النوادي';

    protected static ?string $recordTitleAttribute = 'club_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Club Identity')
                ->columns(2)
                ->schema([
                    TextInput::make('club_name')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),
                    TextInput::make('club_order')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('Qualification Thresholds')
                ->columns(3)
                ->schema([
                    TextInput::make('required_increase')
                        ->label('Required New Lines')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->suffix('lines'),
                    TextInput::make('required_transfer_count')
                        ->label('Min Transfer Lines')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->suffix('lines'),
                    TextInput::make('required_transfer_percentage')
                        ->label('Transfer % (60% Rule)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(1)
                        ->step(0.01),
                ]),

            Section::make('Reward Configuration')
                ->columns(3)
                ->schema([
                    TextInput::make('base_reward_amount')
                        ->label('Entry Reward (NIS)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₪'),
                    TextInput::make('first_arrival_reward_amount')
                        ->label('First Arrival Reward (NIS)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₪'),
                    TextInput::make('first_arrival_count')
                        ->label('First Arrival Slots')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('Lottery Configuration')
                ->columns(3)
                ->schema([
                    TextInput::make('seat_capacity')
                        ->label('Min Agents to Unlock Draw')
                        ->required()
                        ->integer()
                        ->minValue(1),
                    TextInput::make('grand_prize_amount')
                        ->label('Grand Prize (NIS)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₪'),
                    TextInput::make('entry_opportunities')
                        ->label('Entry Lottery Tickets')
                        ->required()
                        ->integer()
                        ->minValue(1),
                ]),

            Section::make('Demotion & Bonus')
                ->columns(3)
                ->schema([
                    TextInput::make('demotion_timer_days')
                        ->label('Demotion Timer (days)')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->suffix('days'),
                    Toggle::make('has_bonus_opportunities')
                        ->label('Has Bonus Opportunities')
                        ->reactive(),
                    TextInput::make('bonus_per_numbers')
                        ->label('Lines per Bonus Ticket')
                        ->integer()
                        ->minValue(1)
                        ->nullable()
                        ->hidden(fn ($get) => ! $get('has_bonus_opportunities')),
                ]),

            Section::make('Status')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('club_order')
                    ->label('#')
                    ->sortable()
                    ->width('50px'),
                TextColumn::make('club_name')
                    ->label('Club')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('required_increase')
                    ->label('Required Lines')
                    ->suffix(' lines')
                    ->sortable(),
                TextColumn::make('base_reward_amount')
                    ->label('Entry Reward')
                    ->money('ILS')
                    ->sortable(),
                TextColumn::make('first_arrival_reward_amount')
                    ->label('1st Arrival Reward')
                    ->money('ILS'),
                TextColumn::make('grand_prize_amount')
                    ->label('Grand Prize')
                    ->money('ILS')
                    ->sortable(),
                TextColumn::make('seat_capacity')
                    ->label('Min for Draw')
                    ->suffix(' agents'),
                TextColumn::make('agents_count')
                    ->label('Current Members')
                    ->counts('agents')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('club_order')
            ->filters([
                TernaryFilter::make('is_active')->label('Status'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListClubs::route('/'),
            'create' => Pages\CreateClub::route('/create'),
            'view'   => Pages\ViewClub::route('/{record}'),
            'edit'   => Pages\EditClub::route('/{record}/edit'),
        ];
    }
}
