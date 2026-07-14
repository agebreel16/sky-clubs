<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // الوكلاء الجدد يبدأون دائماً خارج الأندية — مطابق لسلوك الاستيراد الجماعي
        // (ProcessAgentImport::processRow). أهلية النادي تُكتشَف لاحقاً عبر مسارات
        // المزامنة الطبيعية وتمر بتدفق الموافقة (ClubChangeRequest) كباقي الوكلاء.
        $data['current_club_id'] = null;
        return $data;
    }
}
