<div>
    {{-- Header + Filter --}}
    <div class="section-head" style="margin-bottom:16px;">
        <div>
            <h2 style="font-size:22px;display:flex;align-items:center;gap:8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
                سجل الأحداث
            </h2>
            <div class="card-subtitle">{{ $logs->count() }} حدث · من الأحدث للأقدم</div>
        </div>
        @if($logs->isNotEmpty() || $filter !== 'all')
            <div class="meta">
                {{ $filter === 'all' && $logs->isNotEmpty() ? 'منذ ' . $logs->last()->event_timestamp?->diffForHumans(now(), true) : '' }}
            </div>
        @endif
    </div>

    {{-- Filter Buttons --}}
    @php
        $filters = [
            'all'         => 'الكل',
            'promotion'   => 'ترقية',
            'demotion'    => 'تهبيط',
            'warning'     => 'تحذير',
            'achievement' => 'إنجاز',
            'info'        => 'معلومة',
        ];
    @endphp
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
        @foreach($filters as $key => $label)
            <button wire:click="$set('filter', '{{ $key }}')"
                    style="padding:5px 14px;border-radius:999px;border:1.5px solid {{ $filter === $key ? '#0ea5e9' : 'var(--slate-200)' }};background:{{ $filter === $key ? '#e0f2fe' : 'transparent' }};color:{{ $filter === $key ? '#0ea5e9' : 'var(--slate-500)' }};font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Timeline --}}
    <div class="tl">
        @forelse($logs as $log)
            @php
                $typeMap = [
                    'promotion'   => ['tc' => 'promo', 'label' => 'ترقية',
                        'svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>'],
                    'demotion'    => ['tc' => 'demo',  'label' => 'تهبيط',
                        'svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>'],
                    'warning'     => ['tc' => 'warn',  'label' => 'تحذير',
                        'svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>'],
                    'achievement' => ['tc' => 'ach',   'label' => 'إنجاز',
                        'svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'],
                    'info'        => ['tc' => 'info',  'label' => 'معلومة',
                        'svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'],
                ];
                $cfg  = $typeMap[$log->event_type] ?? ['tc' => 'info', 'label' => $log->event_type,
                    'svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>'];
                $from = $log->fromClub?->club_name ?? 'خارج الأندية';
                $to   = $log->toClub?->club_name;
            @endphp
            <div class="tl-item t-{{ $cfg['tc'] }}">
                <div class="tl-dot" style="display:flex;align-items:center;justify-content:center;">{!! $cfg['svg'] !!}</div>
                <div class="tl-card">
                    <div class="tl-head">
                        <div class="tl-title">
                            {{ $cfg['label'] }}
                            @if($to)
                                <span style="font-weight:400;color:var(--slate-600);font-size:13px;margin-right:6px;">{{ $from }} → {{ $to }}</span>
                            @endif
                        </div>
                        <div class="tl-date">{{ $log->event_timestamp?->format('Y-m-d') }}</div>
                    </div>
                    @if($log->reason)
                        <div class="tl-desc">{{ $log->reason }}</div>
                    @endif
                </div>
            </div>
        @empty
            <div style="text-align:center;padding:60px;color:var(--slate-400);">
                <div style="margin-bottom:12px;color:var(--slate-300);">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <div>{{ $filter === 'all' ? 'لا توجد أحداث مسجّلة بعد' : 'لا توجد أحداث من هذا النوع' }}</div>
            </div>
        @endforelse
    </div>
</div>
