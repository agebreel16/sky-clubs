<?php

namespace App\Filament\Resources\AgentImportResource\Pages;

use App\Filament\Resources\AgentImportResource;
use App\Jobs\ProcessAgentImport;
use App\Models\Agent;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewAgentImport extends ViewRecord
{
    protected static string $resource = AgentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reprocess')
                ->label('إعادة المعالجة')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn () => in_array($this->record->status, ['success', 'rolled_back']))
                ->action(function () {
                    ProcessAgentImport::dispatch($this->record);

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
                ->hidden(fn () => $this->record->status !== 'success')
                ->action(function () {
                    DB::transaction(function () {
                        Agent::where('agent_import_id', $this->record->import_id)->delete();

                        $this->record->update([
                            'status'         => 'rolled_back',
                            'rolled_back_at' => now(),
                        ]);
                    });

                    Notification::make()
                        ->title('تم التراجع عن الاستيراد')
                        ->body('تم حذف الوكلاء المُنشأين من هذا الملف بنجاح.')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'rolled_back_at']);
                }),
        ];
    }
}
