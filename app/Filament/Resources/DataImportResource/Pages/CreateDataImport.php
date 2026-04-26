<?php

namespace App\Filament\Resources\DataImportResource\Pages;

use App\Filament\Resources\DataImportResource;
use App\Jobs\ProcessDataImport;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDataImport extends CreateRecord
{
    protected static string $resource = DataImportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status']      = 'pending';
        $data['uploaded_by'] = Auth::id();
        return $data;
    }

    protected function afterCreate(): void
    {
        ProcessDataImport::dispatch($this->record);

        Notification::make()
            ->title('تم رفع الملف بنجاح')
            ->body('بدأت معالجة البيانات في الخلفية. ستظهر النتائج فور الانتهاء.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
