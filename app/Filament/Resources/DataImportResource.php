<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DataImportResource\Pages;
use App\Models\DataImport;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Tables\Table;

class DataImportResource extends Resource
{
    protected static ?string $model = DataImport::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-arrow-up-tray'; }

    public static function getNavigationGroup(): ?string { return 'إدارة البيانات'; }

    protected static ?int $navigationSort = 1;

    protected static ?string $label = 'استيراد بيانات';

    protected static ?string $pluralLabel = 'استيراد البيانات';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('بيانات الاستيراد')
                ->columns(2)
                ->schema([
                    DatePicker::make('data_date')
                        ->label('تاريخ البيانات')
                        ->required(),
                    Select::make('source_type')
                        ->label('نوع المصدر')
                        ->options([
                            'excel' => 'ملف Excel',
                            'api'   => 'API',
                        ])
                        ->required()
                        ->default('excel'),
                    FileUpload::make('stored_filepath')
                        ->label('ملف Excel')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->visible(fn ($get) => $get('source_type') === 'excel')
                        ->storeFileNamesIn('original_filename'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('data_date')
                    ->label('تاريخ البيانات')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('original_filename')
                    ->label('اسم الملف')
                    ->limit(35),
                TextColumn::make('total_agents')
                    ->label('إجمالي')
                    ->sortable(),
                TextColumn::make('processed')
                    ->label('معالج')
                    ->sortable(),
                TextColumn::make('rejected')
                    ->label('مرفوض')
                    ->sortable(),
                TextColumn::make('promotions_count')
                    ->label('ترقيات')
                    ->sortable(),
                TextColumn::make('demotions_count')
                    ->label('تهبيطات')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(function (string $state): string {
                        if ($state === 'success')    { return 'success'; }
                        if ($state === 'failed')     { return 'danger'; }
                        if ($state === 'processing') { return 'info'; }
                        if ($state === 'pending')    { return 'warning'; }
                        return 'gray';
                    })
                    ->formatStateUsing(function (string $state): string {
                        if ($state === 'success')    { return 'مكتمل'; }
                        if ($state === 'failed')     { return 'فشل'; }
                        if ($state === 'processing') { return 'جارٍ المعالجة'; }
                        if ($state === 'pending')    { return 'في الانتظار'; }
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
                Action::make('process')
                    ->label('إعادة المعالجة')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn ($record) => $record->status === 'success')
                    ->action(function (DataImport $record) {
                        \App\Jobs\ProcessDataImport::dispatch($record);

                        Notification::make()
                            ->title('بدأت عملية المعالجة')
                            ->body('يتم الآن معالجة البيانات في الخلفية.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDataImports::route('/'),
            'create' => Pages\CreateDataImport::route('/create'),
            'view'   => Pages\ViewDataImport::route('/{record}'),
        ];
    }
}
