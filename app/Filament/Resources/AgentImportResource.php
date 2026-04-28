<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentImportResource\Pages;
use App\Jobs\ProcessAgentImport;
use App\Models\Agent;
use App\Models\AgentImport;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class AgentImportResource extends Resource
{
    protected static ?string $model = AgentImport::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-users'; }

    public static function getNavigationGroup(): ?string { return 'إدارة البيانات'; }

    protected static ?int $navigationSort = 2;

    protected static ?string $label = 'استيراد وكلاء';

    protected static ?string $pluralLabel = 'استيراد الوكلاء';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('رفع ملف الوكلاء')
                ->schema([
                    FileUpload::make('stored_filepath')
                        ->label('ملف Excel')
                        ->disk('local')
                        ->directory('agent-imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->storeFileNamesIn('original_filename'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_filename')
                    ->label('اسم الملف')
                    ->limit(40),
                TextColumn::make('total_rows')
                    ->label('إجمالي الصفوف')
                    ->sortable(),
                TextColumn::make('created_count')
                    ->label('وكلاء جدد')
                    ->sortable(),
                TextColumn::make('updated_count')
                    ->label('تم تحديثهم')
                    ->sortable(),
                TextColumn::make('rejected_count')
                    ->label('مرفوض')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'success')      { return 'success'; }
                        if ($state === 'failed')       { return 'danger'; }
                        if ($state === 'processing')   { return 'info'; }
                        if ($state === 'pending')      { return 'warning'; }
                        if ($state === 'rolled_back')  { return 'gray'; }
                        return 'gray';
                    })
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'success')      { return 'مكتمل'; }
                        if ($state === 'failed')       { return 'فشل'; }
                        if ($state === 'processing')   { return 'جارٍ المعالجة'; }
                        if ($state === 'pending')      { return 'في الانتظار'; }
                        if ($state === 'rolled_back')  { return 'تم التراجع'; }
                        return $state;
                    }),
                TextColumn::make('created_at')
                    ->label('وقت الرفع')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'    => 'في الانتظار',
                        'processing' => 'جارٍ المعالجة',
                        'success'    => 'مكتمل',
                        'failed'     => 'فشل',
                    ]),
            ])
            ->actions([
                ViewAction::make()->label('عرض'),
                Action::make('reprocess')
                    ->label('إعادة المعالجة')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => in_array($record->status, ['success', 'rolled_back']))
                    ->action(function (AgentImport $record) {
                        ProcessAgentImport::dispatch($record);

                        Notification::make()
                            ->title('بدأت عملية المعالجة')
                            ->body('يتم الآن معالجة بيانات الوكلاء في الخلفية.')
                            ->success()
                            ->send();
                    }),

                Action::make('rollback')
                    ->label('تراجع عن الاستيراد')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد التراجع عن الاستيراد')
                    ->modalDescription('سيتم حذف جميع الوكلاء الذين أُنشئوا من هذا الملف. الوكلاء الذين تم تحديثهم فقط لن يتأثروا. هذا الإجراء لا يمكن التراجع عنه.')
                    ->modalSubmitActionLabel('نعم، تراجع عن الاستيراد')
                    ->modalCancelActionLabel('إلغاء')
                    ->hidden(fn ($record) => $record->status !== 'success')
                    ->action(function (AgentImport $record) {
                        DB::transaction(function () use ($record) {
                            Agent::where('agent_import_id', $record->import_id)->delete();

                            $record->update([
                                'status'          => 'rolled_back',
                                'rolled_back_at'  => now(),
                            ]);
                        });

                        Notification::make()
                            ->title('تم التراجع عن الاستيراد')
                            ->body('تم حذف الوكلاء المُنشأين من هذا الملف بنجاح.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\AgentImportResource\RelationManagers\AgentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAgentImports::route('/'),
            'create' => Pages\CreateAgentImport::route('/create'),
            'view'   => Pages\ViewAgentImport::route('/{record}'),
        ];
    }
}
