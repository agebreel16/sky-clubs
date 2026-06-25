<div class="block-grid cols-2" style="align-items:start;">

    {{-- آخر 4 أسابيع --}}
    <div class="card card-pad">
        <div class="section-head" style="margin:0 0 20px;">
            <div>
                <div style="font-size:11px;letter-spacing:.15em;color:var(--slate-500);text-transform:uppercase;">أداؤك الأسبوعي</div>
                <div style="font-size:20px;font-weight:700;color:var(--slate-900);margin-top:2px;">آخر 4 أسابيع</div>
            </div>
        </div>

        @foreach($weeks as $i => $w)
            @php
                $isCurrent = $w['is_current'];
                $prev      = $weeks[$i - 1]['total'] ?? null;
                $diff      = $prev !== null ? $w['total'] - $prev : null;
                $barColor  = $isCurrent
                    ? '#0ea5e9'
                    : ($i === 0 ? '#cbd5e1' : ($diff >= 0 ? '#10b981' : '#ef4444'));
            @endphp
            <div style="margin-bottom:18px;">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <div>
                        <div style="font-size:13px;font-weight:{{ $isCurrent ? '700' : '500' }};color:{{ $isCurrent ? 'var(--slate-900)' : 'var(--slate-600)' }};">
                            {{ $w['label'] }}
                        </div>
                        <div style="font-size:11px;color:var(--slate-400);margin-top:1px;">{{ $w['date_range'] }}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        @if($diff !== null)
                            <span style="font-size:11px;font-weight:600;color:{{ $diff > 0 ? '#10b981' : ($diff < 0 ? '#ef4444' : 'var(--slate-400)') }};">
                                {{ $diff > 0 ? '↑ +' : ($diff < 0 ? '↓ ' : '= ') }}{{ abs($diff) }}
                            </span>
                        @endif
                        <span style="font-size:{{ $isCurrent ? '20px' : '16px' }};font-weight:700;color:{{ $isCurrent ? '#0ea5e9' : 'var(--slate-700)' }};">
                            {{ number_format($w['total']) }}
                        </span>
                    </div>
                </div>
                <div style="background:#f1f5f9;border-radius:8px;height:{{ $isCurrent ? '12px' : '8px' }};overflow:hidden;">
                    <div style="width:0%;height:100%;border-radius:8px;background:{{ $barColor }};transition:width 1s ease-out;"
                         data-fill-width="{{ $w['pct'] }}%"></div>
                </div>
            </div>
        @endforeach

        @if(empty($weeks) || collect($weeks)->sum('total') === 0)
            <div style="text-align:center;padding:40px;color:var(--slate-400);">
                <div style="margin-bottom:10px;color:var(--slate-300);"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div>
                <div>لا توجد بيانات أداء بعد</div>
            </div>
        @endif
    </div>

    {{-- مقارنة الشهرين --}}
    <div class="card card-pad">
        <div class="section-head" style="margin:0 0 16px;">
            <div>
                <div style="font-size:11px;letter-spacing:.15em;color:var(--slate-500);text-transform:uppercase;">مقارنة الشهور</div>
                <div style="font-size:20px;font-weight:700;color:var(--slate-900);margin-top:2px;">{{ $thisMonthLabel }} vs {{ $prevMonthLabel }}</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div style="text-align:center;padding:20px;background:#e0f2fe;border-radius:14px;">
                <div style="font-size:32px;font-weight:900;color:#0ea5e9;"
                     x-data="countUp({{ $thisMonthTotal }}, 900)" x-init="init()">
                    <span x-text="formatted">{{ $thisMonthTotal }}</span>
                </div>
                <div style="font-size:12px;color:#0369a1;margin-top:6px;font-weight:600;">{{ $thisMonthLabel }}</div>
            </div>
            <div style="text-align:center;padding:20px;background:#f8fafc;border-radius:14px;">
                <div style="font-size:32px;font-weight:900;color:#94a3b8;">{{ $prevMonthTotal }}</div>
                <div style="font-size:12px;color:#94a3b8;margin-top:6px;font-weight:500;">{{ $prevMonthLabel }}</div>
            </div>
        </div>
        @if($prevMonthTotal > 0)
            @php
                $monthDiff = $thisMonthTotal - $prevMonthTotal;
                $pct       = round(abs($monthDiff / $prevMonthTotal) * 100);
            @endphp
            <div style="text-align:center;margin-top:16px;padding:12px;border-radius:10px;font-size:14px;font-weight:600;
                        background:{{ $monthDiff >= 0 ? '#f0fdf4' : '#fff1f2' }};
                        color:{{ $monthDiff >= 0 ? '#16a34a' : '#dc2626' }};">
                {{ $monthDiff >= 0 ? '↑ تحسّن بمقدار ' : '↓ انخفض بمقدار ' }}{{ abs($monthDiff) }} ({{ $pct }}%)
            </div>
        @elseif($thisMonthTotal > 0)
            <div style="text-align:center;margin-top:16px;padding:12px;background:#f0fdf4;border-radius:10px;font-size:13px;color:#16a34a;">
                لا توجد بيانات للشهر السابق
            </div>
        @endif
    </div>

</div>
