<?php

namespace App\Filament\Resources\ClubChangeRequestResource\Pages;

use App\Filament\Resources\ClubChangeRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;

class ListClubChangeRequests extends ListRecords
{
    protected static string $resource = ClubChangeRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_archive')
                ->label('أرشيف الطلبات')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->url(fn () => ClubChangeRequestResource::getUrl('archive')),
        ];
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(fn ($query) => $query->where('status', 'pending'));
    }
}
