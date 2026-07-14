<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource;
use App\Filament\Resources\ClubChangeRequestResource\Pages;
use App\Models\Agent;
use App\Models\AgentNotification;
use App\Models\Club;
use App\Models\ClubChangeRequest;
use App\Models\HistoryLog;
use App\Models\Opportunity;
use App\Models\Reward;
use App\Notifications\AgentPortalNotification;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClubChangeRequestResource extends Resource
{
    protected static ?string $model = ClubChangeRequest::class;

    public static function getNavigationIcon(): string { return 'heroicon-o-clipboard-document-check'; }
    public static function getNavigationGroup(): string { return 'العمليات'; }
    public static function getNavigationLabel(): string { return 'طلبات تغيير النادي'; }
    public static function getModelLabel(): string { return 'طلب تغيير النادي'; }
    public static function getPluralModelLabel(): string { return 'طلبات تغيير النادي'; }
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = ClubChangeRequest::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('agent.agent_name')
                    ->label('الوكيل')
                    ->description(fn ($record) => $record->agent?->distributor?->name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('change_type')
                    ->label('نوع التغيير')
                    ->badge()
                    ->color(fn ($state) => $state === 'promotion' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 'promotion' ? '↑ ترقية' : '↓ تهبيط'),

                TextColumn::make('fromClub.club_name')
                    ->label('من')
                    ->default('خارج الأندية')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('toClub.club_name')
                    ->label('إلى')
                    ->default('خارج الأندية')
                    ->badge()
                    ->color(fn ($record) => $record->change_type === 'promotion' ? 'success' : 'warning'),

                IconColumn::make('reason')
                    ->label('السبب')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color(fn ($record) => $record->change_type === 'promotion' ? 'success' : 'danger')
                    ->alignCenter()
                    ->getStateUsing(fn () => true)
                    ->tooltip(fn (ClubChangeRequest $record) => static::reasonText($record)),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending'        => 'warning',
                        'approved'       => 'success',
                        'rejected'       => 'danger',
                        'auto_cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending'        => 'معلّق',
                        'approved'       => 'مقبول',
                        'rejected'       => 'مرفوض',
                        'auto_cancelled' => 'ملغى',
                    }),

                TextColumn::make('reviewer.name')
                    ->label('راجعه')
                    ->default('—'),

                TextColumn::make('created_at')
                    ->label('تاريخ الاكتشاف')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'        => 'معلّق',
                        'approved'       => 'مقبول',
                        'rejected'       => 'مرفوض',
                        'auto_cancelled' => 'ملغى',
                    ])
                    ->default('pending'),

                SelectFilter::make('change_type')
                    ->label('النوع')
                    ->options([
                        'promotion' => 'ترقية',
                        'demotion'  => 'تهبيط',
                    ]),
            ])
            ->actions([
                Action::make('view_agent')
                    ->label('عرض الوكيل')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (ClubChangeRequest $record) => AgentResource::getUrl('view', ['record' => $record->agent_id]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->agent_id !== null),

                Action::make('approve')
                    ->label('قبول')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalWidth('md')
                    ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
                    ->modalHeading('تأكيد القبول')
                    ->modalDescription('سيُطبَّق تغيير النادي فوراً على حساب الوكيل.')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function (ClubChangeRequest $record) {
                        static::approveRequest($record);
                    }),

                Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->modalWidth('xl')
                    ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
                    ->modalHeading('رفض طلب تغيير النادي')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form(fn ($record) => [
                        Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Toggle::make('mark_as_violator')
                            ->label('تحويل الوكيل إلى قائمة المخالفين')
                            ->helperText('استخدم هذا الخيار عند الاشتباه في التلاعب بالأرقام')
                            ->visible($record->change_type === 'promotion')
                            ->default(false),
                    ])
                    ->action(function (ClubChangeRequest $record, array $data) {
                        static::rejectRequest($record, $data);
                    }),
            ])
            ->bulkActions([
                BulkAction::make('bulk_approve_promotions')
                    ->label('قبول الترقيات المحددة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('قبول الترقيات المحددة')
                    ->modalDescription('سيُقبَل طلبات الترقية المعلّقة فقط. طلبات التهبيط أو المراجعة السابقة تُتجاهل تلقائياً.')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records) {
                        $approved = 0;
                        $skipped  = 0;
                        $failed   = 0;

                        foreach ($records as $record) {
                            if ($record->change_type !== 'promotion' || $record->status !== 'pending') {
                                $skipped++;
                                continue;
                            }
                            try {
                                static::approveRequest($record, silent: true);
                                $approved++;
                            } catch (\Exception $e) {
                                $failed++;
                                \Illuminate\Support\Facades\Log::error("Bulk approve failed for {$record->id}: " . $e->getMessage());
                            }
                        }

                        $message = "تمت الموافقة على {$approved} طلب ترقية";
                        if ($skipped) $message .= " | تجاهل {$skipped}";
                        if ($failed)  $message .= " | فشل {$failed}";

                        Notification::make()
                            ->success()
                            ->title($message)
                            ->send();
                    }),
            ])
            ->poll('30s');
    }

    protected static function reasonText(ClubChangeRequest $record): string
    {
        $snapshot = $record->agent_stats_snapshot ?? [];
        $increase = (int) ($snapshot['campaign_increase'] ?? 0);
        $transfer = (int) ($snapshot['transfer_count'] ?? 0);

        if ($record->change_type === 'promotion') {
            $club = $record->toClub;
            return $club
                ? "حقق شروط {$club->club_name}: زيادة {$increase}/{$club->required_increase} • تحويل {$transfer}/{$club->required_transfer_count}"
                : '—';
        }

        $club = $record->fromClub;
        if (! $club) {
            return '—';
        }

        $unmet = [];
        if ($increase < $club->required_increase) {
            $unmet[] = "الزيادة {$increase}/{$club->required_increase}";
        }
        if ($transfer < $club->required_transfer_count) {
            $unmet[] = "التحويل {$transfer}/{$club->required_transfer_count}";
        }

        return $unmet
            ? 'لم يعد يحقق: ' . implode(' و', $unmet)
            : "لم يعد ضمن {$club->club_name}";
    }

    protected static function approveRequest(ClubChangeRequest $record, bool $silent = false): void
    {
        // حارس ضد double-approve (race condition أو تحديث الصفحة المزدوج)
        $fresh = $record->fresh();
        if (! $fresh || $fresh->status !== 'pending') {
            Notification::make()->warning()->title('تمت معالجة هذا الطلب مسبقاً')->send();
            return;
        }

        $agent  = $record->agent;
        $toClub = $record->toClub;

        if (! $agent) {
            Notification::make()->danger()->title('الوكيل غير موجود')->send();
            return;
        }

        // حارس idempotency: إذا كان الوكيل موجوداً بالفعل في النادي المستهدف (مثلاً طُبِّق
        // التغيير عبر مسار آخر — AgentObserver عند تعديل يدوي — قبل مراجعة هذا الطلب)،
        // لا تُنشئ Reward/HistoryLog مكرراً. الطلب أصبح غير ذي صلة، وليس "مُعتمَداً" فعلياً.
        if ($agent->current_club_id === $record->to_club_id) {
            $record->update([
                'status'      => 'auto_cancelled',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            Notification::make()
                ->warning()
                ->title('الوكيل بالفعل في هذه الحالة — تم إلغاء الطلب تلقائياً (طُبِّق عبر مسار آخر)')
                ->send();
            return;
        }

        $updateData = ['entry_date' => now()];

        if ($record->change_type === 'promotion' && $toClub) {
            $isFirst = Agent::where('current_club_id', $toClub->club_id)->count() < $toClub->first_arrival_count;
            $updateData['current_club_id'] = $toClub->club_id;
            $updateData['is_first_arrival'] = $isFirst;

            // Query Builder — يتجاوز Observer لمنع ازدواجية Reward/Opportunity
            Agent::where('agent_id', $agent->agent_id)->update($updateData);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'promotion',
                'from_club_id'    => $record->from_club_id,
                'to_club_id'      => $toClub->club_id,
                'reason'          => 'قبول طلب الترقية من قِبَل الإدارة',
                'event_timestamp' => now(),
            ]);

            Reward::create([
                'agent_id'         => $agent->agent_id,
                'club_id'          => $toClub->club_id,
                'amount'           => $isFirst
                                        ? $toClub->first_arrival_reward_amount
                                        : $toClub->base_reward_amount,
                'is_first_arrival' => $isFirst,
                'payment_status'   => 'pending',
            ]);

            $existingEntryClubIds = $agent->opportunities()
                ->where('type', 'entry')
                ->where('is_active', true)
                ->pluck('club_id')
                ->all();
            $clubsUpTo = Club::where('club_order', '<=', $toClub->club_order)
                ->orderBy('club_order')
                ->get();
            foreach ($clubsUpTo as $club) {
                if (!in_array($club->club_id, $existingEntryClubIds)) {
                    Opportunity::create([
                        'agent_id'    => $agent->agent_id,
                        'club_id'     => $club->club_id,
                        'type'        => 'entry',
                        'earned_date' => now(),
                        'is_active'   => true,
                    ]);
                }
            }

            if ($isFirst) {
                Opportunity::create([
                    'agent_id'    => $agent->agent_id,
                    'club_id'     => $toClub->club_id,
                    'type'        => 'first_arrival',
                    'earned_date' => now(),
                    'is_active'   => true,
                ]);
            }

            // الإشعار للوكيل — فقط عند قبول الترقية
            $promoTitle = 'مبروك الترقية! 🏆';
            $promoBody  = "تهانينا! لقد انضممت إلى {$toClub->club_name}.";

            AgentNotification::create([
                'agent_id'          => $agent->agent_id,
                'club_id'           => $toClub->club_id,
                'notification_type' => 'promotion',
                'title'             => $promoTitle,
                'body'              => $promoBody,
                'category'          => 'in_club',
                'sent_at'           => now(),
            ]);

            if ($agent->portal_token) {
                $agent->notify(new AgentPortalNotification(
                    title:   $promoTitle,
                    body:    $promoBody,
                    sendSms: false,
                ));
            }
        } else {
            // تهبيط
            $updateData['current_club_id'] = $record->to_club_id; // null إذا خارج الأندية
            Agent::where('agent_id', $agent->agent_id)->update($updateData);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'demotion',
                'from_club_id'    => $record->from_club_id,
                'to_club_id'      => $record->to_club_id,
                'reason'          => 'قبول طلب التهبيط من قِبَل الإدارة',
                'event_timestamp' => now(),
            ]);

            $fromClub      = $record->from_club_id ? Club::find($record->from_club_id) : null;
            $demotionTitle = 'تغيير في عضوية نادي';
            $demotionBody  = $toClub
                ? "تم نقلك من {$fromClub->club_name} إلى {$toClub->club_name}."
                : "تم خروجك من {$fromClub->club_name}.";

            AgentNotification::create([
                'agent_id'          => $agent->agent_id,
                'club_id'           => $record->from_club_id,
                'notification_type' => 'demotion',
                'title'             => $demotionTitle,
                'body'              => $demotionBody,
                'category'          => 'in_club',
                'sent_at'           => now(),
            ]);

            if ($agent->portal_token) {
                $agent->notify(new AgentPortalNotification(title: $demotionTitle, body: $demotionBody));
            }
        }

        $record->update([
            'status'      => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if (! $silent) {
            Notification::make()->success()->title('تم القبول وتطبيق التغيير')->send();
        }
    }

    protected static function rejectRequest(ClubChangeRequest $record, array $data): void
    {
        // حارس ضد double-reject
        $fresh = $record->fresh();
        if (! $fresh || $fresh->status !== 'pending') {
            Notification::make()->warning()->title('تمت معالجة هذا الطلب مسبقاً')->send();
            return;
        }

        $agent = $record->agent;

        $record->update([
            'status'           => 'rejected',
            'rejection_reason' => $data['rejection_reason'],
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        HistoryLog::create([
            'agent_id'        => $agent->agent_id,
            'event_type'      => 'rejection',
            'from_club_id'    => $record->from_club_id,
            'to_club_id'      => $record->to_club_id,
            'reason'          => $data['rejection_reason'],
            'event_timestamp' => now(),
        ]);

        $toClub         = $record->toClub;
        $rejectionTitle = $record->change_type === 'promotion'
            ? 'طلب الترقية لم يُقبَل'
            : 'طلب المراجعة لم يُقبَل';
        $rejectionBody  = $record->change_type === 'promotion' && $toClub
            ? "لم يتم قبول طلب انضمامك إلى {$toClub->club_name}."
            : "لم يتم قبول طلب مراجعة عضويتك.";

        AgentNotification::create([
            'agent_id'          => $agent->agent_id,
            'club_id'           => $record->to_club_id,
            'notification_type' => 'warning',
            'title'             => $rejectionTitle,
            'body'              => $rejectionBody,
            'category'          => 'in_club',
            'sent_at'           => now(),
        ]);

        if ($agent->portal_token) {
            $agent->notify(new AgentPortalNotification(title: $rejectionTitle, body: $rejectionBody));
        }

        if (! empty($data['mark_as_violator'])) {
            Agent::where('agent_id', $agent->agent_id)->update([
                'is_violator'     => true,
                'violator_since'  => now(),
                'violator_reason' => $data['rejection_reason'],
            ]);

            HistoryLog::create([
                'agent_id'        => $agent->agent_id,
                'event_type'      => 'violation',
                'from_club_id'    => null,
                'to_club_id'      => $agent->current_club_id,
                'reason'          => $data['rejection_reason'],
                'event_timestamp' => now(),
            ]);
            // لا إشعار للوكيل عند تصنيف المخالفة

            Notification::make()->success()->title('تم الرفض وتصنيف الوكيل كمخالف')->send();
        } else {
            Notification::make()->success()->title('تم رفض الطلب')->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClubChangeRequests::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
