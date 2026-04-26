<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-user-group'; }

    public static function getNavigationGroup(): ?string { return 'إدارة النظام'; }

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'مستخدم';

    protected static ?string $pluralLabel = 'المستخدمون';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Personal Information')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('employee_id')
                        ->label('Employee ID')
                        ->unique(ignoreRecord: true)
                        ->nullable()
                        ->maxLength(50),
                    TextInput::make('phone')
                        ->tel()
                        ->nullable()
                        ->maxLength(20),
                    TextInput::make('position')
                        ->nullable()
                        ->maxLength(100),
                ]),

            Section::make('Role & Department')
                ->columns(2)
                ->schema([
                    Select::make('role')
                        ->options([
                            'super_admin'    => 'Super Admin',
                            'admin'          => 'Admin',
                            'supervisor'     => 'Supervisor',
                            'data_entry'     => 'Data Entry',
                            'viewer'         => 'Viewer',
                            'finance_officer' => 'Finance Officer',
                        ])
                        ->required(),
                    Select::make('department')
                        ->options([
                            'admin'      => 'Admin',
                            'supervisor' => 'Supervisor',
                            'data_entry' => 'Data Entry',
                            'finance'    => 'Finance',
                            'support'    => 'Support',
                        ])
                        ->required()
                        ->default('data_entry'),
                ]),

            Section::make('Authentication')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->minLength(8),
                    TextInput::make('email_verified_at')
                        ->label('Email Verified At')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                ]),

            Section::make('Account Status')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('Active Account')
                        ->default(true),
                    Toggle::make('requires_password_change')
                        ->label('Force Password Change'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'super_admin'     => 'danger',
                        'admin'           => 'warning',
                        'supervisor'      => 'info',
                        'data_entry'      => 'success',
                        'finance_officer' => 'primary',
                        'viewer'          => 'gray',
                        default           => 'gray',
                    }),
                TextColumn::make('department')
                    ->badge(),
                TextColumn::make('position')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'super_admin'     => 'Super Admin',
                        'admin'           => 'Admin',
                        'supervisor'      => 'Supervisor',
                        'data_entry'      => 'Data Entry',
                        'viewer'          => 'Viewer',
                        'finance_officer' => 'Finance Officer',
                    ]),
                SelectFilter::make('department')
                    ->options([
                        'admin'      => 'Admin',
                        'supervisor' => 'Supervisor',
                        'data_entry' => 'Data Entry',
                        'finance'    => 'Finance',
                        'support'    => 'Support',
                    ]),
                TernaryFilter::make('is_active')->label('Status'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view'   => Pages\ViewUser::route('/{record}'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
