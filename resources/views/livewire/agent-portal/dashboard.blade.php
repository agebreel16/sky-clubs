<div>
    {{-- Hero Card --}}
    @if($agent->club)
        @php
            $rankInClub = \App\Models\Agent::where('current_club_id', $agent->current_club_id)
                ->whereNotNull('current_club_id')
                ->orderByDesc('transfer_count')
                ->pluck('agent_id')
                ->search($agent->agent_id);
            $rankInClub = ($rankInClub !== false) ? $rankInClub + 1 : '—';
            $daysIn      = $agent->entry_date ? (int) $agent->entry_date->diffInDays(now()) : 0;
            $isTop       = !\App\Models\Club::where('is_active', true)->where('club_order', '>', $agent->club->club_order)->exists();
        @endphp
        <div class="hero">
            <div class="grid-bg"></div>
            <div class="hero-row">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="30" height="30"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                </div>
                <div style="flex:1;">
                    <div class="hero-eyebrow">ناديك الحالي</div>
                    <div class="hero-title">{{ $agent->club->club_name }}</div>
                    <div class="hero-sub">{{ $isTop ? 'المستوى الأعلى' : 'عضو نشط' }}</div>
                </div>
                @if($agent->is_first_arrival)
                    <div class="first-arrival-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                        أول وصول
                    </div>
                @endif
            </div>
            <div class="hero-divider"></div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    انضممت منذ {{ $daysIn }} يوماً
                </div>
                <div class="rank-medal">
                    @php
                        $rankStyles = [
                            1 => 'background:#eab308;color:white;',
                            2 => 'background:#94a3b8;color:white;',
                            3 => 'background:#cd7f32;color:white;',
                        ];
                        $rs = $rankStyles[$rankInClub] ?? 'background:var(--slate-200);color:var(--slate-700);';
                    @endphp
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;{{ $rs }}font-size:10px;font-weight:700;">
                        {{ is_numeric($rankInClub) && $rankInClub <= 3 ? $rankInClub : '#' . $rankInClub }}
                    </span>
                    رتبتك #{{ $rankInClub }} في النادي
                </div>
                @if($agent->last_self_sync_at)
                    <div class="hero-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                        آخر مزامنة: {{ $agent->last_self_sync_at->diffForHumans() }}
                    </div>
                @endif
                <div class="hero-meta-item live-dot">اتصال حي</div>
            </div>
        </div>
    @else
        @php
            $firstClub = \App\Models\Club::where('is_active', true)->orderBy('club_order')->first();
            $needed    = $firstClub ? max(0, $firstClub->required_increase - ($agent->transfer_count + $agent->new_line_count)) : 0;
        @endphp
        <div class="hero" style="background:linear-gradient(135deg,#475569 0%,#64748b 100%);">
            <div class="grid-bg"></div>
            <div class="hero-row">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="30" height="30"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>
                </div>
                <div style="flex:1;">
                    <div class="hero-eyebrow">حالتك</div>
                    <div class="hero-title">لم تنضم لأي نادٍ بعد</div>
                    <div class="hero-sub">تحتاج {{ $needed }} زيادة فقط للوصول إلى {{ $firstClub?->club_name ?? 'نادي البداية' }}</div>
                </div>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <strong>ابدأ الآن وانضم إلى أول نادٍ</strong>
                    <span style="display:inline-block;animation:pulse 1.4s infinite;">←</span>
                </div>
                @if($agent->last_self_sync_at)
                    <div class="hero-meta-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                        آخر مزامنة: {{ $agent->last_self_sync_at->diffForHumans() }}
                    </div>
                @endif
                <div class="hero-meta-item live-dot">اتصال حي</div>
            </div>
        </div>
    @endif

    {{-- Violator Banner --}}
    @if($agent->is_violator)
        <div class="warn" style="border-color:#ef4444;background:rgba(239,68,68,0.08);">
            <div class="warn-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>
            </div>
            <div class="warn-body">
                <div class="warn-title" style="color:#ef4444;">تنبيه: حسابك مُعلَّق من قِبَل الإدارة</div>
                <div class="warn-desc">للاستفسار تواصل مع مشرفك المباشر.</div>
            </div>
        </div>
    @endif

    {{-- بطاقة الحالة الحية --}}
    @php
        $clubs    = \App\Models\Club::where('is_active', true)->orderBy('club_order')->get();
        $nextClub = $agent->club
            ? $clubs->where('club_order', '>', $agent->club->club_order)->first()
            : $clubs->first();

        if ($agent->is_violator) {
            $agentState = 'violator';
        } elseif (!$agent->club) {
            $agentState = 'no_club';
        } else {
            $agentState = 'safe';
        }

        if ($agent->club) {
            $scIncrease    = $agent->transfer_count + $agent->new_line_count;
            $scNeeded      = $nextClub ? max(0, $nextClub->required_increase - $scIncrease) : 0;
            $scTransferMet = !$nextClub || $agent->transfer_count >= ($nextClub->required_transfer_count ?? 0);
            $scRatioPct    = $nextClub ? ($nextClub->required_transfer_percentage ?? 0) : 0;
            $scAgentRatio  = $agent->campaign_increase > 0
                ? round($agent->transfer_count / $agent->campaign_increase * 100, 1) : 0;
            $scRatioMet    = $scRatioPct <= 0 || $scAgentRatio >= ($scRatioPct * 100);
            $scTotalMet    = !$nextClub || $scIncrease >= $nextClub->required_increase;

            if (!$nextClub) {
                $safeLabel = 'أنت في أعلى نادٍ — استمر في التميز';
                $safeDot = '#16a34a'; $safeBg = '#f0fdf4'; $safeBorder = '#86efac';
            } elseif ($scTotalMet && $scTransferMet && $scRatioMet) {
                $safeLabel = 'مؤهل للترقية إلى ' . $nextClub->club_name . ' — في انتظار التحديث';
                $safeDot = '#10b981'; $safeBg = '#f0fdf4'; $safeBorder = '#6ee7b7';
            } elseif (!$scTransferMet) {
                $remainT   = ($nextClub->required_transfer_count ?? 0) - $agent->transfer_count;
                $safeLabel = 'ركّز على التحويل — متبقٍ ' . $remainT . ' خط للترقية';
                $safeDot = '#d97706'; $safeBg = '#fffbeb'; $safeBorder = '#fde68a';
            } elseif (!$scRatioMet) {
                $safeLabel = 'نسبة التحويل دون المطلوب — ' . $scAgentRatio . '% / ' . ($scRatioPct * 100) . '%';
                $safeDot = '#d97706'; $safeBg = '#fffbeb'; $safeBorder = '#fde68a';
            } elseif ($scNeeded <= (int)ceil($nextClub->required_increase * 0.2)) {
                $safeLabel = 'اقتربت! متبقٍ ' . $scNeeded . ' زيادة لنادي ' . $nextClub->club_name;
                $safeDot = '#0ea5e9'; $safeBg = '#eff6ff'; $safeBorder = '#bae6fd';
            } else {
                $safeLabel = 'وضعك جيد — تحتاج ' . $scNeeded . ' زيادة لنادي ' . $nextClub->club_name;
                $safeDot = '#16a34a'; $safeBg = '#f0fdf4'; $safeBorder = '#86efac';
            }
        } else {
            $safeLabel = ''; $safeDot = '#16a34a'; $safeBg = '#f0fdf4'; $safeBorder = '#86efac';
        }

        $stateMap = [
            'safe'     => ['bg' => $safeBg,   'border' => $safeBorder, 'dot' => $safeDot,   'label' => $safeLabel],
            'violator' => ['bg' => '#fef2f2',  'border' => '#fca5a5',  'dot' => '#dc2626',  'label' => 'الحساب مُعلَّق — تواصل مع مشرفك المباشر'],
            'no_club'  => ['bg' => '#f8fafc',  'border' => '#cbd5e1',  'dot' => '#94a3b8',  'label' => 'لم تنضم لأي نادٍ بعد — ابدأ الآن'],
        ];
        $sc = $stateMap[$agentState];
    @endphp
    <div style="margin-top:16px;padding:14px 18px;background:{{ $sc['bg'] }};border:1.5px solid {{ $sc['border'] }};border-radius:14px;display:flex;align-items:center;gap:12px;">
        <div style="width:10px;height:10px;border-radius:50%;background:{{ $sc['dot'] }};flex-shrink:0;animation:pulse 2s infinite;box-shadow:0 0 0 3px {{ $sc['bg'] }};"></div>
        <div style="font-size:14px;font-weight:600;color:{{ $sc['dot'] }};">{{ $sc['label'] }}</div>
    </div>

    {{-- Grid: الهدف القادم (يسار) + متطلبات+ضغط (يمين) --}}
    @php
        // $clubs و $nextClub محسوبتان مسبقاً في block الحالة الحية أعلاه
    @endphp
    {{-- بطاقة تحفيزية للوكلاء خارج الأندية --}}
    @if(!$agent->club && $nextClub)
    @php
        $motReqTransfer = (int)($nextClub->required_transfer_count ?? 0);
        $motReqNewLines = max(0, (int)($nextClub->required_increase ?? 0) - $motReqTransfer);
        $motGotTransfer = (int)$agent->transfer_count;
        $motGotNewLines = (int)$agent->new_line_count;

        $motTPct = $motReqTransfer > 0 ? min(100, (int)round($motGotTransfer / $motReqTransfer * 100)) : 100;
        $motNPct = $motReqNewLines > 0 ? min(100, (int)round($motGotNewLines / $motReqNewLines * 100)) : 100;

        $motWeakest       = min($motTPct, $motNPct);
        $motFocusTransfer = $motTPct <= $motNPct;
        $motRemainT       = max(0, $motReqTransfer - $motGotTransfer);
        $motRemainN       = max(0, $motReqNewLines - $motGotNewLines);
        $motFocusText     = $motFocusTransfer
            ? "تحتاج {$motRemainT} خط تحويل إضافي"
            : "تحتاج {$motRemainN} خط جديد إضافي";

        [$motStage, $motHeadline, $motAccent] = match(true) {
            $motWeakest === 0  => ['بداية',  'ابدأ رحلتك نحو نادي '.$nextClub->club_name.'!', '#94a3b8'],
            $motWeakest < 30   => ['تقدم',   'رحلتك بدأت — استمر ولا تتوقف!',                 '#0ea5e9'],
            $motWeakest < 60   => ['منتصف',  'في منتصف الطريق — لا تتوقف الآن!',              '#8b5cf6'],
            $motWeakest < 90   => ['اقتراب', 'اقتربت جداً — خطوات قليلة للنادي!',             '#f59e0b'],
            $motWeakest < 100  => ['أبواب',  'على أبواب النادي — الآن أو لا!',                '#10b981'],
            default            => ['مكتمل',  'أتممت جميع الشروط — في انتظار التحديث!',        '#10b981'],
        };
    @endphp
    <div style="margin-top:18px;padding:14px 18px;border-radius:14px;
                background:var(--sc-surface,#fff);
                border:1px solid var(--sc-border,rgba(0,0,0,.09));
                border-top:3px solid {{ $motAccent }};
                box-shadow:var(--sc-shadow,0 2px 12px rgba(0,0,0,.08));
                display:flex;align-items:center;gap:16px;">
        <span style="flex-shrink:0;font-size:11px;font-weight:700;color:{{ $motAccent }};
                     background:{{ $motAccent }}18;padding:3px 10px;border-radius:999px;
                     border:1px solid {{ $motAccent }}40;white-space:nowrap;">
            {{ $motStage }}
        </span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:var(--sc-text,#1e293b);">
                {{ $motHeadline }}
            </div>
            <div style="font-size:12px;color:var(--sc-text2,#64748b);margin-top:2px;">
                {{ $motFocusText }}
            </div>
        </div>
        <div style="flex-shrink:0;text-align:center;">
            <div style="font-size:12px;font-weight:700;color:{{ $motTPct >= 100 ? '#10b981' : $motAccent }};">
                {{ $motTPct >= 100 ? '✓' : $motTPct.'%' }}
            </div>
            <div style="width:48px;height:4px;background:var(--sc-border,#e2e8f0);border-radius:999px;margin:3px 0;overflow:hidden;">
                <div style="width:{{ $motTPct }}%;height:100%;border-radius:999px;
                            background:{{ $motTPct >= 100 ? '#10b981' : $motAccent }};
                            transition:width 1.2s ease-out;"
                     data-fill-width="{{ $motTPct }}%"></div>
            </div>
            <div style="font-size:10px;color:var(--sc-text3,#94a3b8);">تحويل</div>
        </div>
        @if($motReqNewLines > 0)
        <div style="flex-shrink:0;text-align:center;">
            <div style="font-size:12px;font-weight:700;color:{{ $motNPct >= 100 ? '#10b981' : $motAccent }};">
                {{ $motNPct >= 100 ? '✓' : $motNPct.'%' }}
            </div>
            <div style="width:48px;height:4px;background:var(--sc-border,#e2e8f0);border-radius:999px;margin:3px 0;overflow:hidden;">
                <div style="width:{{ $motNPct }}%;height:100%;border-radius:999px;
                            background:{{ $motNPct >= 100 ? '#10b981' : $motAccent }};
                            transition:width 1.2s ease-out;"
                     data-fill-width="{{ $motNPct }}%"></div>
            </div>
            <div style="font-size:10px;color:var(--sc-text3,#94a3b8);">جديدة</div>
        </div>
        @endif
    </div>
    @endif

    <div class="block-grid cols-2" style="margin-top:18px;align-items:start;">

        {{-- العمود الرئيسي: الهدف القادم --}}
        <div>
            <div class="section-head">
                <h2>الهدف القادم</h2>
                <span class="meta">رحلتك في الأندية</span>
            </div>
            @if($agent->club && !$nextClub)
                <div class="next-goal" style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border:1px solid #fde68a;">
                    <div class="next-goal-head">
                        <div class="next-goal-target">
                            <div class="next-goal-target-icon" style="background:var(--grad-gold);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                            </div>
                            <div>
                                <div class="next-goal-eyebrow">الحالة</div>
                                <div class="next-goal-name">أنت في القمة</div>
                            </div>
                        </div>
                        <div class="next-goal-need">
                            <div class="next-goal-need-num">100%</div>
                            <div class="next-goal-need-label">أعلى نادٍ في الحملة</div>
                        </div>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:0%;transition:width 1s ease-out;" data-fill-width="100%"></div></div>
                    <div class="progress-need">استمر في التميز للحفاظ على مكانتك</div>
                </div>
            @else
                @php
                    $agentIncrease = $agent->transfer_count + $agent->new_line_count;
                    $needed        = $nextClub ? max(0, $nextClub->required_increase - $agentIncrease) : 0;
                    $maxR          = $clubs->take(5)->max('required_increase') ?: 1;
                    $fillPct       = min(100, round(($agentIncrease / $maxR) * 100));
                @endphp
                <div class="next-goal">
                    <div class="next-goal-head">
                        <div class="next-goal-target">
                            <div class="next-goal-target-icon">
                                <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M6 2h12l4 7-10 13L2 9z" opacity=".9"/></svg>
                            </div>
                            <div>
                                <div class="next-goal-eyebrow">الهدف القادم</div>
                                <div class="next-goal-name">{{ $nextClub?->club_name ?? 'نادي البداية' }}</div>
                            </div>
                        </div>
                        <div class="next-goal-need">
                            <div class="next-goal-need-num" x-data="countUp({{ $needed }})" x-init="init()">
                                +<span x-text="formatted"></span>
                                <small>زيادة</small>
                            </div>
                            <div class="next-goal-need-label">للوصول</div>
                        </div>
                    </div>
                    <div style="position:relative;">
                        <div class="progress-track">
                            <div class="progress-fill" style="width:0%;transition:width 1s ease-out;" data-fill-width="{{ $fillPct }}%"></div>
                        </div>
                        <div class="milestones">
                            @foreach($clubs->take(5) as $c)
                                @php
                                    $maxR    = $clubs->max('required_increase') ?: 1;
                                    $pct     = round(($c->required_increase / $maxR) * 100);
                                    $passed  = $agent->club && $agent->club->club_order >= $c->club_order;
                                    $current = $agent->club && $agent->current_club_id === $c->club_id;
                                @endphp
                                <div class="milestone {{ $passed ? 'passed' : '' }} {{ $current ? 'current' : '' }}" style="right:{{ $pct }}%;">
                                    <div class="milestone-dot"></div>
                                    <div class="milestone-label">{{ $c->club_name }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="progress-need">
                        تحتاج <strong style="color:var(--primary);font-weight:700;">+{{ $needed }} زيادة</strong> إضافية للوصول إلى {{ $nextClub?->club_name ?? 'أول نادٍ' }}
                    </div>

                    {{-- تفصيل شروط الترقية --}}
                    @if($nextClub)
                        @php
                            $transferMet   = $agent->transfer_count >= $nextClub->required_transfer_count;
                            $totalMet      = $agentIncrease         >= $nextClub->required_increase;
                            $ratioPct      = $nextClub->required_transfer_percentage ?? 0;
                            $agentRatioPct = $agent->campaign_increase > 0
                                ? round($agent->transfer_count / $agent->campaign_increase * 100, 1)
                                : 0;
                            $ratioMet      = $ratioPct <= 0 || $agentRatioPct >= ($ratioPct * 100);
                        @endphp
                        <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">

                            {{-- الشرط 1: إجمالي الزيادة (مع تفصيل التحويل + الجديدة) --}}
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:{{ $totalMet ? '#f0fdf4' : '#fff7ed' }};border:1px solid {{ $totalMet ? '#86efac' : '#fed7aa' }};">
                                <div>
                                    <div style="font-size:12px;font-weight:600;color:#64748b;">إجمالي الزيادة</div>
                                    <div style="font-size:13px;color:#334155;margin-top:1px;">{{ $agentIncrease }} / {{ $nextClub->required_increase }}</div>
                                    <div style="font-size:11px;color:#94a3b8;margin-top:3px;">تحويل: {{ $agent->transfer_count }}  •  جديدة: {{ $agent->new_line_count }}</div>
                                </div>
                                @if($totalMet)
                                    <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:3px 10px;border-radius:999px;">✓ محقق</span>
                                @else
                                    <span style="font-size:11px;font-weight:700;color:#ea580c;background:#ffedd5;padding:3px 10px;border-radius:999px;">متبقٍ {{ $nextClub->required_increase - $agentIncrease }}</span>
                                @endif
                            </div>

                            {{-- الشرط 3: خطوط التحويل --}}
                            @if($nextClub->required_transfer_count > 0)
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:{{ $transferMet ? '#f0fdf4' : '#fff7ed' }};border:1px solid {{ $transferMet ? '#86efac' : '#fed7aa' }};">
                                <div>
                                    <div style="font-size:12px;font-weight:600;color:#64748b;">خطوط التحويل</div>
                                    <div style="font-size:13px;color:#334155;margin-top:1px;">{{ $agent->transfer_count }} / {{ $nextClub->required_transfer_count }}</div>
                                </div>
                                @if($transferMet)
                                    <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:3px 10px;border-radius:999px;">✓ محقق</span>
                                @else
                                    <span style="font-size:11px;font-weight:700;color:#ea580c;background:#ffedd5;padding:3px 10px;border-radius:999px;">متبقٍ {{ $nextClub->required_transfer_count - $agent->transfer_count }}</span>
                                @endif
                            </div>
                            @endif

                            {{-- الشرط 3: نسبة التحويل (إذا مطلوبة) --}}
                            @if($ratioPct > 0)
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;border-radius:10px;background:{{ $ratioMet ? '#f0fdf4' : '#fff7ed' }};border:1px solid {{ $ratioMet ? '#86efac' : '#fed7aa' }};">
                                <div>
                                    <div style="font-size:12px;font-weight:600;color:#64748b;">نسبة التحويل</div>
                                    <div style="font-size:13px;color:#334155;margin-top:1px;">{{ $agentRatioPct }}% / {{ round($ratioPct * 100) }}%</div>
                                </div>
                                @if($ratioMet)
                                    <span style="font-size:11px;font-weight:700;color:#16a34a;background:#dcfce7;padding:3px 10px;border-radius:999px;">✓ محقق</span>
                                @else
                                    <span style="font-size:11px;font-weight:700;color:#ea580c;background:#ffedd5;padding:3px 10px;border-radius:999px;">غير محقق</span>
                                @endif
                            </div>
                            @endif

                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- العمود الجانبي: متطلبات البقاء + Pressure Meter --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- إحصاءات الأداء --}}
            @if($agent->club)
                <div class="card card-pad">
                    <div class="section-head" style="margin:0 0 16px;">
                        <h2>إحصاءات الأداء</h2>
                        <span class="meta">منذ بداية الحملة</span>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                        <div style="padding:14px;background:#eff6ff;border-radius:12px;text-align:center;">
                            <div style="font-size:26px;font-weight:800;color:#2563eb;">{{ number_format($agent->transfer_count) }}</div>
                            <div style="font-size:11px;color:#3b82f6;font-weight:600;margin-top:4px;">خطوط التحويل</div>
                        </div>
                        <div style="padding:14px;background:#f0fdf4;border-radius:12px;text-align:center;">
                            <div style="font-size:26px;font-weight:800;color:#16a34a;">{{ number_format($agent->new_line_count) }}</div>
                            <div style="font-size:11px;color:#22c55e;font-weight:600;margin-top:4px;">خطوط جديدة</div>
                        </div>
                        <div style="padding:14px;background:#faf5ff;border-radius:12px;text-align:center;">
                            <div style="font-size:26px;font-weight:800;color:#7c3aed;">{{ number_format($agent->campaign_increase) }}</div>
                            <div style="font-size:11px;color:#8b5cf6;font-weight:600;margin-top:4px;">إجمالي الزيادة</div>
                        </div>
                        <div style="padding:14px;background:#fff7ed;border-radius:12px;text-align:center;">
                            <div style="font-size:26px;font-weight:800;color:#ea580c;">{{ number_format($agent->transfer_percentage, 1) }}%</div>
                            <div style="font-size:11px;color:#f97316;font-weight:600;margin-top:4px;">نسبة التحويل</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- فرصك المكتسبة --}}
            @php
                $opps      = $agent->opportunities()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
                $entryOpps = (int)($opps['entry'] ?? 0);
                $firstOpps = (int)($opps['first_arrival'] ?? 0);
                $bonusOpps = (int)($opps['bonus'] ?? 0);
                $maintOpps = (int)($opps['maintenance'] ?? 0);
                $totalOpps = $entryOpps + $firstOpps + $bonusOpps + $maintOpps;
            @endphp
            <div class="card card-pad">
                <div class="section-head" style="margin:0 0 14px;">
                    <h2>فرصك المكتسبة</h2>
                    <span class="meta" style="font-size:18px;font-weight:800;color:var(--primary);">{{ $totalOpps }} فرصة</span>
                </div>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    @foreach([
                        ['label' => 'فرص الدخول',   'val' => $entryOpps, 'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
                        ['label' => 'أول وصول',      'val' => $firstOpps, 'color' => '#f59e0b', 'bg' => '#fffbeb'],
                        ['label' => 'فرص إضافية',    'val' => $bonusOpps, 'color' => '#8b5cf6', 'bg' => '#faf5ff'],
                        ['label' => 'المحافظة شهرية',  'val' => $maintOpps, 'color' => '#10b981', 'bg' => '#f0fdf4'],
                    ] as $row)
                        @if($row['val'] > 0)
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:{{ $row['bg'] }};border-radius:10px;">
                                <span style="font-size:13px;font-weight:500;color:#475569;">{{ $row['label'] }}</span>
                                <span style="font-size:16px;font-weight:800;color:{{ $row['color'] }};">{{ $row['val'] }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if($totalOpps === 0)
                        <div style="text-align:center;padding:24px;color:var(--slate-400);font-size:13px;">
                            لم تكسب فرص سحب بعد — انضم لنادٍ لتبدأ
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
