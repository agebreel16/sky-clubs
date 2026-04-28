<?php

namespace App\Filament\Resources\AgentImportResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'agents';

    protected static ?string $title = 'الوكلاء المُضافون';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('agent_name')
                    ->label('اسم الوكيل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('baseline_count')
                    ->label('الأساس')
                    ->sortable(),

                TextColumn::make('pre_campaign_count')
                    ->label('الأرقام القديمة')
                    ->sortable(),

                TextColumn::make('current_total')
                    ->label('الإجمالي الحالي')
                    ->sortable(),

                TextColumn::make('club.club_name')
                    ->label('النادي')
                    ->badge()
                    ->default('—'),
            ])
            ->defaultSort('agent_name')
            ->searchPlaceholder('بحث بالاسم...')
            ->paginated([10, 25, 50]);
    }
}
