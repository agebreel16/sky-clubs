<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\Agent;
use App\Models\AppSetting;
use App\Support\DealsApiCalculator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

class ViewAgent extends ViewRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()->label('حذف'),

            // Generate token if missing, then show copyable URL in modal
            Action::make('share_portal_link')
                ->label('شارك الرابط')
                ->icon('heroicon-o-share')
                ->color('success')
                ->mountUsing(function (Agent $record) {
                    if (!$record->portal_token) {
                        $record->generatePortalToken();
                    }
                })
                ->modalHeading('رابط بوابة الوكيل')
                ->modalContent(fn (Agent $record) => view('filament.agent.portal-link-modal', [
                    'url' => $record->fresh()->getPortalUrl(),
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق'),

            // فحص تشخيصي: يعرض الرد الخام الكامل من كلا الـ API لهذا الوكيل تحديداً،
            // بنفس معاملات from/to التي تستخدمها مهام المزامنة الفعلية — للتأكد يدوياً
            // أن الاستجابة لا تحتوي مشكلة، بدون أي تلخيص أو اشتقاق للأرقام.
            Action::make('inspect_api_response')
                ->label('API TEST')
                ->icon('heroicon-o-code-bracket-square')
                ->color('gray')
                ->modalWidth('5xl')
                ->modalHeading('API RESPONSE')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('إغلاق')
                ->modalContent(fn (Agent $record) => view(
                    'filament.agent.api-inspector-modal',
                    $this->fetchRawApiResponses($record)
                )),

            // Invalidate old token and generate a new one
            Action::make('regenerate_portal_link')
                ->label('تجديد الرابط')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('تجديد رابط البوابة')
                ->modalDescription('سيُبطَل الرابط القديم فوراً. الوكيل لن يتمكن من الدخول بالرابط السابق.')
                ->action(function (Agent $record) {
                    $record->generatePortalToken();
                    Notification::make()->title('تم تجديد الرابط بنجاح')->success()->send();
                }),
        ];
    }

    /**
     * يستدعي GetSubCustomerDeals وGetSubCustomerActiveSubs لهذا الوكيل تحديداً (نفس
     * from/to المستخدمة فعلياً في ProcessDataImport/ProcessAgentSelfSync)، ويُرجع
     * الرد الخام الكامل لكل منهما لعرضه في مودال تشخيصي — بدون أي تلخيص أو اشتقاق.
     */
    private function fetchRawApiResponses(Agent $record): array
    {
        $url      = AppSetting::get('deals_api_url');
        $username = AppSetting::get('deals_api_username');
        $password = AppSetting::get('deals_api_password');
        $from     = AppSetting::get('deals_campaign_start_date', '2026-05-17');
        $to       = today()->format('Y-m-d');

        $context = ['agentId' => $record->agent_id, 'from' => $from, 'to' => $to];

        if (! $url || ! $username) {
            return [
                'configured' => false,
                'context'    => $context,
                'results'    => [],
            ];
        }

        $responses = Http::pool(fn (Pool $pool) => [
            'GetSubCustomerDeals' => $pool->as('GetSubCustomerDeals')
                ->withoutVerifying()
                ->timeout(15)
                ->post($url, DealsApiCalculator::buildPayload($username, $password, 'GetSubCustomerDeals', $record->agent_id, $from, $to)),
            'GetSubCustomerActiveSubs' => $pool->as('GetSubCustomerActiveSubs')
                ->withoutVerifying()
                ->timeout(15)
                ->post($url, DealsApiCalculator::buildPayload($username, $password, 'GetSubCustomerActiveSubs', $record->agent_id, $from, $to)),
        ]);

        $results = [];

        foreach (['GetSubCustomerDeals', 'GetSubCustomerActiveSubs'] as $apiName) {
            $response = $responses[$apiName] ?? null;

            if (! $response || $response instanceof \Exception) {
                $results[$apiName] = [
                    'ok'     => false,
                    'error'  => $response instanceof \Exception ? $response->getMessage() : 'لا استجابة من الـ API',
                    'status' => null,
                    'raw'    => null,
                ];
                continue;
            }

            $body = $response->json();

            $results[$apiName] = [
                'ok'     => true,
                'error'  => null,
                'status' => $response->status(),
                'raw'    => $body !== null
                    ? json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $response->body(),
                'isJson' => $body !== null,
            ];
        }

        return [
            'configured' => true,
            'context'    => $context,
            'results'    => $results,
        ];
    }

    /**
     * بطاقات قسم "الأرقام والإحصائيات" — تُعرَض عبر resources/views/filament/agent/stats-grid.blade.php
     * بنفس نظام sc-stat-card المستخدَم في campaign-stats-overview.blade.php (متوافق تلقائياً مع الوضع الداكن/الفاتح).
     *
     * @return array<int, array{value: int|string, label: string, color: string, icon: string}>
     */
    private function statsTiles(Agent $record): array
    {
        $tiles = [

            [
                'value' => number_format($record->baseline_count),
                'label' => 'الخطوط قبل الحملة',
                'color' => 'var(--sc-text3)',
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>',
            ],
            [
                'value' => number_format($record->true_active_subs),
                'label' => 'الإجمالي حتى الآن',
                'color' => 'var(--sc-accent)',
                'icon'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>',
            ],
        ];

        return $tiles;
    }

    /**
     * صف المعادلة المميّز: الخطوط داخل الحملة = خطوط التحويل + الخطوط الجديدة.
     * يُعرَض كسطر واحد مستقل فوق شبكة البطاقات لإبراز هذه العلاقة الحسابية بصرياً.
     *
     * @return array{total: array{value: string, label: string, color: string}, parts: array<int, array{value: string, label: string, color: string}>}
     */
    private function campaignEquation(Agent $record): array
    {
        return [
            'total' => [
                'value' => number_format($record->campaign_increase),
                'label' => 'الخطوط داخل الحملة',
                'color' => 'var(--sc-green)',
            ],
            'parts' => [
                [
                    'value' => number_format($record->transfer_count),
                    'label' => 'خطوط التحويل',
                    'color' => 'var(--sc-purple)',
                ],
                [
                    'value' => number_format($record->new_line_count),
                    'label' => 'الخطوط الجديدة',
                    'color' => 'var(--sc-gold)',
                ],
            ],
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([

            // ── Row 1: Identity + Club Side by Side ───────────────────────────
            Grid::make(2)->schema([

                Section::make('بيانات الوكيل')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor('primary')
                    ->schema([
                        TextEntry::make('agent_name')
                            ->label('الاسم')
                            ->size('lg')
                            ->weight('bold'),

                        TextEntry::make('phone')
                            ->label('رقم الجوال')
                            ->icon('heroicon-m-phone')
                            ->default('—'),

                        TextEntry::make('agent_id')
                            ->label('المعرّف')
                            ->copyable()
                            ->fontFamily('mono')
                            ->color('gray'),

                        TextEntry::make('distributor.name')
                            ->label('الموزع')
                            ->default('—')
                            ->badge()
                            ->color('info')
                            ->url(fn (Agent $record): ?string => $record->distributor_id
                                ? \App\Filament\Resources\DistributorResource::getUrl('view', ['record' => $record->distributor_id])
                                : null),

                        TextEntry::make('created_at')
                            ->label('تاريخ التسجيل')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-m-calendar-days'),

                        TextEntry::make('updated_at')
                            ->label('آخر تحديث')
                            ->dateTime('d/m/Y H:i')
                            ->icon('heroicon-m-arrow-path'),
                    ]),

                Section::make('حالة النادي')
                    ->icon('heroicon-o-trophy')
                    ->iconColor('warning')
                    ->visible(fn (Agent $record) => $record->current_club_id !== null)
                    ->schema([
                        TextEntry::make('club.club_name')
                            ->label('النادي الحالي')
                            ->badge()
                            ->size('lg')
                            ->weight('bold')
                            ->color(fn (Agent $record): string => match ((int) ($record->club?->club_order ?? 0)) {
                                1       => 'success',
                                2       => 'info',
                                3       => 'warning',
                                default => 'gray',
                            }),

                        Grid::make(2)->schema([
                            TextEntry::make('entry_date')
                                ->label('تاريخ الدخول')
                                ->dateTime('d/m/Y')
                                ->icon('heroicon-m-arrow-right-circle'),

                            TextEntry::make('days_in_club')
                                ->label('المدة في النادي')
                                ->getStateUsing(fn (Agent $record): string => !$record->entry_date
                                    ? '—'
                                    : (int) $record->entry_date->diffInDays(now()) . ' يوم')
                                ->icon('heroicon-m-clock'),

                            TextEntry::make('club_rank')
                                ->label('الترتيب')
                                ->getStateUsing(function (Agent $record): string {
                                    if (!$record->current_club_id) return '—';
                                    $rank  = Agent::where('current_club_id', $record->current_club_id)
                                        ->where('transfer_count', '>', $record->transfer_count)
                                        ->count() + 1;
                                    $total = Agent::where('current_club_id', $record->current_club_id)->count();
                                    return "#{$rank} من {$total}";
                                })
                                ->badge()
                                ->color('primary'),

                            IconEntry::make('is_first_arrival')
                                ->label('من الأوائل')
                                ->boolean()
                                ->trueIcon('heroicon-o-star')
                                ->falseIcon('heroicon-o-minus-circle')
                                ->trueColor('warning')
                                ->falseColor('gray'),
                        ]),
                    ]),

            ]),

            // ── Row 2: KPI Stats Grid ──────────────────────────────────────────
            Section::make('الأرقام والإحصائيات')
                ->icon('heroicon-o-chart-bar-square')
                ->iconColor('info')
                ->schema([
                    ViewEntry::make('stats')
                        ->view('filament.agent.stats-grid')
                        ->viewData(fn (Agent $record) => [
                            'tiles'    => $this->statsTiles($record),
                            'equation' => $this->campaignEquation($record),
                            'agent'    => $record,
                        ])
                        ->columnSpanFull(),
                ]),

            // ── Row 3: Violator Warning ───────────────────────────────────────
            Section::make('تحذير — هذا الوكيل مصنّف ضمن المخالفين')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('danger')
                ->visible(fn (Agent $record) => $record->is_violator)
                ->columns(2)
                ->schema([
                    TextEntry::make('violator_since')
                        ->label('تاريخ التصنيف')
                        ->dateTime('d/m/Y')
                        ->icon('heroicon-m-clock')
                        ->badge()
                        ->color('danger'),

                    TextEntry::make('violator_reason')
                        ->label('السبب')
                        ->badge()
                        ->color('danger')
                        ->columnSpanFull(),
                ]),

            // ── Row 4: Notes ──────────────────────────────────────────────────
            Section::make('ملاحظات')
                ->icon('heroicon-o-document-text')
                ->iconColor('gray')
                ->visible(fn (Agent $record) => !empty($record->notes))
                ->schema([
                    TextEntry::make('notes')
                        ->label('')
                        ->columnSpanFull(),
                ]),

        ]);
    }
}
