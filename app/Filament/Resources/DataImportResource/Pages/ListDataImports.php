<?php

namespace App\Filament\Resources\DataImportResource\Pages;

use App\Filament\Resources\DataImportResource;
use Filament\Resources\Pages\ListRecords;

class ListDataImports extends ListRecords
{
    protected static string $resource = DataImportResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
