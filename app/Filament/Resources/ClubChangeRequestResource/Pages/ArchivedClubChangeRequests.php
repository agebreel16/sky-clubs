<?php

namespace App\Filament\Resources\ClubChangeRequestResource\Pages;

use App\Exports\ClubChangeRequestsExport;
use App\Filament\Resources\ClubChangeRequestResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ArchivedClubChangeRequests extends ListRecords
{
    protected static string $resource = ClubChangeRequestResource::class;

    protected static ?string $title = 'أرشيف طلبات التغيير';

    public function table(Table $table): Table
    {
        return $table->poll(null);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_pending')
                ->label('طلبات معلّقة')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->url(fn () => ClubChangeRequestResource::getUrl('index')),

            Action::make('export_archive')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()
                        ->with(['agent.distributor', 'fromClub', 'toClub', 'reviewer'])
                        ->get();

                    return app(ClubChangeRequestsExport::class)->download($records);
                }),
        ];
    }

    public function getTabs(): array
    {
        $counts = DB::table('club_change_requests')
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $labels = [
            'pending'        => 'معلّقة',
            'approved'       => 'مقبولة',
            'rejected'       => 'مرفوضة',
            'auto_cancelled' => 'ملغاة تلقائياً',
        ];

        $tabs = [
            'all' => Tab::make('الكل')
                ->badge((int) $counts->sum())
                ->badgeColor('primary'),
        ];

        foreach ($labels as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->badge((int) ($counts[$status] ?? 0))
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return $tabs;
    }
}
