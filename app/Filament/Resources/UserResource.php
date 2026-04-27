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
            Section::make('المعلومات الشخصية')
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
                        ->label('معرف الموظف')
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

            Section::make('الدور والقسم')
                ->columns(2)
                ->schema([
                    Select::make('role')
                        ->options([
                            'super_admin'    => 'مسؤول عام',
                            'admin'          => 'مسؤول',
                            'supervisor'     => 'مشرف',
                            'data_entry'     => 'إدخال البيانات',
                            'viewer'         => 'عارض',
                            'finance_officer' => 'مسؤول مالي',
                        ])
                        ->required(),
                    Select::make('department')
                        ->options([
                            'admin'      => 'إدارة',
                            'supervisor' => 'إشراف',
                            'data_entry' => 'إدخال البيانات',
                            'finance'    => 'مالية',
                            'support'    => 'دعم',
                        ])
                        ->required()
                        ->default('data_entry'),
                ]),

            Section::make('المصادقة')
                ->columns(2)
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->minLength(8),
                    TextInput::make('email_verified_at')
                        ->label('البريد مُتحقق منه في')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                ]),

            Section::make('حالة الحساب')
                ->columns(2)
                ->schema([
                    Toggle::make('is_active')
                        ->label('حساب نشط')
                        ->default(true),
                    Toggle::make('requires_password_change')
                        ->label('فرض تغيير كلمة المرور'),
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
                    ->label('نشط')
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('آخر تسجيل دخول')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'super_admin'     => 'مسؤول عام',
                        'admin'           => 'مسؤول',
                        'supervisor'      => 'مشرف',
                        'data_entry'      => 'إدخال البيانات',
                        'viewer'          => 'عارض',
                        'finance_officer' => 'مسؤول مالي',
                    ]),
                SelectFilter::make('department')
                    ->options([
                        'admin'      => 'إدارة',
                        'supervisor' => 'إشراف',
                        'data_entry' => 'إدخال البيانات',
                        'finance'    => 'مالية',
                        'support'    => 'دعم',
                    ]),
                TernaryFilter::make('is_active')->label('الحالة'),
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
