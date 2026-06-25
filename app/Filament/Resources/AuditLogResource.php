<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-shield-check'; }

    public static function getNavigationGroup(): ?string { return 'السجلات'; }

    protected static ?int $navigationSort = 3;

    protected static ?string $label = 'سجل التدقيق';

    protected static ?string $pluralLabel = 'سجلات التدقيق';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->default('System'),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'create'       => 'success',
                        'update'       => 'info',
                        'delete'       => 'danger',
                        'login'        => 'gray',
                        'failed_login' => 'warning',
                        'import'       => 'primary',
                        'export'       => 'primary',
                        'rollback'     => 'danger',
                        default        => 'gray',
                    }),
                TextColumn::make('model_type')
                    ->label('النموذج')
                    ->badge(),
                TextColumn::make('model_id')
                    ->label('معرف السجل')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->limit(80),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'success' => 'success',
                        'failure' => 'danger',
                        default   => 'gray',
                    }),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('الوقت')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'create'       => 'إنشاء',
                        'read'         => 'قراءة',
                        'update'       => 'تحديث',
                        'delete'       => 'حذف',
                        'export'       => 'تصدير',
                        'import'       => 'استيراد',
                        'rollback'     => 'استرجاع',
                        'login'        => 'تسجيل دخول',
                        'failed_login' => 'فشل تسجيل الدخول',
                    ]),
                SelectFilter::make('model_type')
                    ->label('النموذج')
                    ->options([
                        'Agent'      => 'وكيل',
                        'Club'       => 'نادي',
                        'DataImport' => 'استيراد البيانات',
                        'User'       => 'مستخدم',
                        'Reward'     => 'مكافأة',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        'success' => 'نجح',
                        'failure' => 'فشل',
                    ]),
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
