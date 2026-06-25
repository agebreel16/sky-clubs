<div>
    {{-- Lottery Hero --}}
    <div class="lott-hero">
        <div style="position:relative;">
            <div class="lott-hero-eyebrow" style="display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                فرص السحب
            </div>
            <div class="lott-hero-num" x-data="countUp({{ $total }})" x-init="init()">
                <span x-text="formatted"></span><small>فرصة</small>
            </div>
        </div>
        <div class="lott-pie">
            <div class="lott-pie-bar">
                <div style="display:flex;height:100%;">
                    @foreach($grouped as $clubId => $clubOpps)
                        @php
                            $colors = ['#06b6d4','#eab308','#94a3b8','#8b5cf6','#10b981'];
                            $pctBar = $total > 0 ? round($clubOpps->count() / $total * 100) : 0;
                        @endphp
                        <div style="width:{{ $pctBar }}%;background:{{ $colors[$loop->index % count($colors)] }};"></div>
                    @endforeach
                </div>
            </div>
        </div>
        <div style="position:relative;display:flex;gap:18px;margin-top:10px;font-size:11px;opacity:.85;flex-wrap:wrap;">
            @foreach($grouped as $clubId => $clubOpps)
                @php $color = ['#06b6d4','#eab308','#94a3b8','#8b5cf6','#10b981'][$loop->index % 5]; @endphp
                <span style="display:inline-flex;align-items:center;gap:6px;">
                    <span style="width:8px;height:8px;border-radius:2px;background:{{ $color }};display:inline-block;"></span>
                    {{ $clubOpps->first()->club?->club_name ?? 'غير محدد' }} ({{ $clubOpps->count() }})
                </span>
            @endforeach
        </div>
    </div>

    {{-- Grid: أقسام النوادي + دليل كيف تكسب --}}
    <div class="block-grid cols-2" style="margin-top:18px;align-items:start;">

    {{-- العمود الرئيسي: أقسام النوادي --}}
    <div>
        @forelse($grouped as $clubId => $clubOpps)
            @php $clubName = $clubOpps->first()->club?->club_name ?? 'غير محدد'; @endphp
            <div class="lott-club">
                <div class="lott-club-head">
                    <div class="lott-club-icon entry">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                    </div>
                    <div class="lott-club-name">{{ $clubName }}</div>
                    <div class="lott-club-total">إجمالي: <strong>{{ $clubOpps->count() }} فرصة</strong></div>
                </div>
                @foreach($clubOpps->groupBy('type') as $type => $typeOpps)
                    @php
                        $typeLabels = ['entry' => 'دخول النادي', 'first_arrival' => 'أول وصول', 'maintenance' => 'محافظة شهرية', 'bonus' => 'مكافأة'];
                        $label  = $typeLabels[$type] ?? $type;
                        $barPct = $clubOpps->count() > 0 ? round($typeOpps->count() / $clubOpps->count() * 100) : 0;
                    @endphp
                    <div class="ticket-row">
                        <div class="ticket-icon">
                            @if($type === 'entry')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2M13 17v2M13 11v2"/></svg>
                            @elseif($type === 'first_arrival')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            @elseif($type === 'maintenance')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>
                            @else
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
                            @endif
                        </div>
                        <div class="ticket-label">{{ $label }}</div>
                        <div class="ticket-bar">
                            <div class="ticket-bar-fill {{ $type }}" style="width:0%;transition:width 1.2s;" data-fill-width="{{ $barPct }}%"></div>
                        </div>
                        <div class="ticket-count">{{ $typeOpps->count() }} فرصة</div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="empty">لا توجد فرص سحب بعد</div>
        @endforelse
    </div>

    {{-- العمود الجانبي: دليل كيف تكسب فرص السحب؟ --}}
    <div class="card card-pad">
        <div class="section-head" style="margin:0 0 16px;">
            <div>
                <div style="font-size:11px;letter-spacing:.15em;color:var(--slate-500);text-transform:uppercase;">دليل الفرص</div>
                <div style="font-size:18px;font-weight:700;color:var(--slate-900);margin-top:2px;">كيف تكسب فرص السحب؟</div>
            </div>
        </div>

        {{-- مؤشر المحافظة الشهرية --}}
        <div style="padding:12px 16px;border-radius:12px;background:{{ $maintenanceThisMonth ? '#f0fdf4' : '#fff7ed' }};border:1px solid {{ $maintenanceThisMonth ? '#86efac' : '#fdba74' }};margin-bottom:18px;display:flex;align-items:center;gap:12px;">
            <div style="width:32px;height:32px;border-radius:8px;background:{{ $maintenanceThisMonth ? '#10b98120' : '#ea580c20' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                @if($maintenanceThisMonth)
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                @else
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                @endif
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:{{ $maintenanceThisMonth ? '#16a34a' : '#ea580c' }};">
                    فرصة المحافظة الشهرية — {{ now()->translatedFormat('F Y') }}
                </div>
                <div style="font-size:11px;color:var(--slate-600);margin-top:2px;">
                    {{ $maintenanceThisMonth ? 'تم استلام فرصة المحافظة الشهرية لهذا الشهر' : 'لم تستلم فرصة المحافظة الشهرية بعد — حافظ على نشاطك' }}
                </div>
            </div>
        </div>

        {{-- أنواع الفرص --}}
        @php
            $howToEarn = [
                [
                    'color' => '#06b6d4',
                    'title' => 'دخول النادي',
                    'desc'  => $club
                        ? ($club->entry_opportunities . ' ' . ($club->entry_opportunities === 1 ? 'فرصة سحب' : 'فرص سحب') . ' عند دخول كل نادٍ')
                        : 'فرص سحب عند دخول كل نادٍ',
                ],
                [
                    'color' => '#eab308',
                    'title' => 'أول وصول',
                    'desc'  => $club
                        ? ('مكافأة لأول ' . $club->first_arrival_count . ' وكيل يدخل النادي')
                        : 'مكافأة لأوائل الوكلاء الداخلين للنادي',
                ],
                [
                    'color' => '#10b981',
                    'title' => 'محافظة شهرية',
                    'desc'  => 'فرصة سحب إضافية عن كل شهر تحافظ فيه على عضويتك في النادي',
                ],
            ];
            if ($club && $club->has_bonus_opportunities && $club->bonus_per_numbers) {
                $howToEarn[] = [
                    'color' => '#8b5cf6',
                    'title' => 'مكافأة الأداء',
                    'desc'  => 'فرصة سحب إضافية لكل ' . $club->bonus_per_numbers . ' تحويل',
                ];
            }
        @endphp
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($howToEarn as $h)
                <div style="display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;background:#f8fafc;">
                    <div style="width:8px;height:8px;border-radius:50%;background:{{ $h['color'] }};flex-shrink:0;"></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:var(--slate-800);">{{ $h['title'] }}</div>
                        <div style="font-size:11px;color:var(--slate-500);margin-top:1px;">{{ $h['desc'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    </div>{{-- end block-grid --}}
</div>
