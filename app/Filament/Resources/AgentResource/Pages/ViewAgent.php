<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\Agent;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAgent extends ViewRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()->label('حذف'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Section 1: Basic Info ──────────────────────────────────────────
            Section::make('المعلومات الأساسية')
                ->columns(2)
                ->schema([
                    TextEntry::make('agent_name')
                        ->label('اسم الوكيل')
                        ->weight('bold'),

                    TextEntry::make('agent_id')
                        ->label('معرّف الوكيل')
                        ->copyable()
                        ->fontFamily('mono'),

                    TextEntry::make('created_at')
                        ->label('تاريخ التسجيل')
                        ->dateTime('d/m/Y H:i'),

                    TextEntry::make('updated_at')
                        ->label('آخر تحديث')
                        ->dateTime('d/m/Y H:i'),
                ]),

            // ── Section 2: Statistics ──────────────────────────────────────────
            Section::make('الأرقام والإحصائيات')
                ->columns(4)
                ->schema([
                    TextEntry::make('baseline_count')
                        ->label('الأساس (مجمّد)')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('pre_campaign_count')
                        ->label('الأرقام القديمة')
                        ->badge()
                        ->color('gray'),

                    TextEntry::make('current_total')
                        ->label('الإجمالي الحالي')
                        ->badge()
                        ->color('info'),

                    TextEntry::make('campaign_increase_calc')
                        ->label('الزيادة في الحملة')
                        ->getStateUsing(fn (Agent $record) => $record->current_total - $record->pre_campaign_count)
                        ->badge()
                        ->color('success'),

                    TextEntry::make('transfer_count')
                        ->label('التحويلات')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('new_line_count')
                        ->label('الخطوط الجديدة')
                        ->badge()
                        ->color('primary'),

                    TextEntry::make('transfer_pct_calc')
                        ->label('نسبة التحويل')
                        ->getStateUsing(function (Agent $record): string {
                            if (!$record->club) { return '—'; }
                            $req = (int) $record->club->required_increase;
                            if ($req === 0) { return '0%'; }
                            return round(($record->transfer_count / $req) * 100, 1) . '%';
                        })
                        ->badge()
                        ->color(function (Agent $record): string {
                            if (!$record->club) { return 'gray'; }
                            $req = (int) $record->club->required_increase;
                            if ($req === 0) { return 'gray'; }
                            return ($record->transfer_count / $req) * 100 >= 60 ? 'success' : 'danger';
                        }),

                    TextEntry::make('baseline_loss_calc')
                        ->label('الأرقام المفقودة')
                        ->getStateUsing(fn (Agent $record) => $record->baseline_count - $record->pre_campaign_count)
                        ->badge()
                        ->color(fn (Agent $record) => ($record->baseline_count - $record->pre_campaign_count) > 0 ? 'danger' : 'gray'),
                ]),

            // ── Section 3: Club Status ─────────────────────────────────────────
            Section::make('حالة النادي')
                ->columns(3)
                ->visible(fn (Agent $record) => $record->current_club_id !== null)
                ->schema([
                    TextEntry::make('club.club_name')
                        ->label('النادي الحالي')
                        ->badge()
                        ->color(function (Agent $record): string {
                            $order = $record->club ? (int) $record->club->club_order : 0;
                            if ($order === 1) { return 'success'; }
                            if ($order === 2) { return 'info'; }
                            if ($order === 3) { return 'warning'; }
                            return 'gray';
                        }),

                    TextEntry::make('entry_date')
                        ->label('تاريخ الدخول')
                        ->dateTime('d/m/Y'),

                    TextEntry::make('days_in_club')
                        ->label('الأيام في النادي')
                        ->getStateUsing(function (Agent $record): string {
                            if (!$record->entry_date) { return '—'; }
                            return $record->entry_date->diffInDays(now()) . ' يوم';
                        }),

                    IconEntry::make('is_first_arrival')
                        ->label('من الأوائل')
                        ->boolean(),

                    TextEntry::make('club_rank')
                        ->label('الترتيب في النادي')
                        ->getStateUsing(function (Agent $record): string {
                            if (!$record->current_club_id) { return '—'; }
                            $rank = Agent::where('current_club_id', $record->current_club_id)
                                ->where('current_total', '>', $record->current_total)
                                ->count() + 1;
                            $total = Agent::where('current_club_id', $record->current_club_id)->count();
                            return "#{$rank} من {$total}";
                        }),
                ]),

            // ── Section 4: Demotion Status ─────────────────────────────────────
            Section::make('حالة العداد')
                ->columns(3)
                ->visible(fn (Agent $record) => $record->demotion_timer_start !== null)
                ->schema([
                    TextEntry::make('demotion_timer_start')
                        ->label('بداية العداد')
                        ->dateTime('d/m/Y H:i')
                        ->badge()
                        ->color('warning'),

                    TextEntry::make('days_left_calc')
                        ->label('أيام متبقية')
                        ->getStateUsing(function (Agent $record): string {
                            if (!$record->demotion_timer_start || !$record->club) { return '—'; }
                            $deadline = $record->demotion_timer_start->copy()->addDays($record->club->demotion_timer_days);
                            return max(0, (int) now()->diffInDays($deadline, false)) . ' يوم';
                        })
                        ->badge()
                        ->color('danger'),

                    TextEntry::make('expected_demotion_date')
                        ->label('تاريخ التهبيط المتوقع')
                        ->getStateUsing(function (Agent $record): string {
                            if (!$record->demotion_timer_start || !$record->club) { return '—'; }
                            return $record->demotion_timer_start->copy()->addDays($record->club->demotion_timer_days)->format('d/m/Y');
                        })
                        ->badge()
                        ->color('danger'),
                ]),

            // ── Section 5: Notes ───────────────────────────────────────────────
            Section::make('ملاحظات')
                ->visible(fn (Agent $record) => !empty($record->notes))
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
