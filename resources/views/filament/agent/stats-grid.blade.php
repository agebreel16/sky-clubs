{{-- بطاقات الأرقام والإحصائيات — تعيد استخدام نظام sc-stat-card الموجود
     فعلاً في admin-theme.blade.php (نفس المستخدَم في campaign-stats-overview) --}}
<style>
@verbatim
.sc-eq-row {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 18px;
    background: var(--sc-surface);
    border: 1px solid var(--sc-border);
    border-radius: var(--sc-radius);
    box-shadow: var(--sc-shadow);
    padding: 24px 28px;
    margin-bottom: 16px;
    position: relative;
    overflow: hidden;
}
.sc-eq-row::before {
    content: '';
    position: absolute;
    inset-inline-start: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: var(--sc-green);
}
.sc-eq-item { text-align: center; min-width: 90px; }
.sc-eq-value {
    font-size: 32px;
    font-weight: 900;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.5px;
}
.sc-eq-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--sc-text3);
    margin-top: 8px;
}
.sc-eq-op {
    font-size: 24px;
    font-weight: 800;
    color: var(--sc-text3);
    opacity: 0.55;
    padding: 0 2px;
}
.sc-eq-total .sc-eq-value { font-size: 40px; }
.sc-eq-total {
    padding: 4px 20px;
    border-radius: 12px;
    background: color-mix(in oklch, var(--sc-green) 8%, transparent);
}
@media (max-width: 640px) {
    .sc-eq-row { padding: 18px 16px; gap: 10px; }
    .sc-eq-value { font-size: 26px; }
    .sc-eq-total .sc-eq-value { font-size: 32px; }
}
.sc-tiles-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}
@media (max-width: 640px) {
    .sc-tiles-row { grid-template-columns: 1fr; gap: 8px; }
}
.sc-decline-warn {
    margin-top: 16px;
    padding: 14px 18px;
    border-radius: var(--sc-radius);
    background: color-mix(in oklch, var(--sc-red) 8%, transparent);
    border: 1px solid color-mix(in oklch, var(--sc-red) 30%, transparent);
    display: flex;
    align-items: center;
    gap: 12px;
}
.sc-decline-warn-icon { flex-shrink: 0; color: var(--sc-red); }
.sc-decline-warn-title { font-weight: 800; color: var(--sc-red); font-size: 14px; }
.sc-decline-warn-sub { font-size: 12px; color: var(--sc-text3); margin-top: 3px; }
@endverbatim
</style>

<div class="sc-eq-row">
    <div class="sc-eq-item sc-eq-total">
        <div class="sc-eq-value" style="color: {{ $equation['total']['color'] }}">{{ $equation['total']['value'] }}</div>
        <div class="sc-eq-label">{{ $equation['total']['label'] }}</div>
    </div>

    <div class="sc-eq-op">=</div>

    <div class="sc-eq-item">
        <div class="sc-eq-value" style="color: {{ $equation['parts'][0]['color'] }}">{{ $equation['parts'][0]['value'] }}</div>
        <div class="sc-eq-label">{{ $equation['parts'][0]['label'] }}</div>
    </div>

    <div class="sc-eq-op">+</div>

    <div class="sc-eq-item">
        <div class="sc-eq-value" style="color: {{ $equation['parts'][1]['color'] }}">{{ $equation['parts'][1]['value'] }}</div>
        <div class="sc-eq-label">{{ $equation['parts'][1]['label'] }}</div>
    </div>
</div>

<div class="sc-tiles-row">
    @foreach($tiles as $tile)
        <div class="sc-stat-card" style="--sc-c: {{ $tile['color'] }}">
            <div class="sc-stat-icon">{!! $tile['icon'] !!}</div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ $tile['value'] }}</div>
                <div class="sc-stat-lbl">{{ $tile['label'] }}</div>
            </div>
        </div>
    @endforeach
</div>

@if($agent->true_deficit !== null && $agent->true_deficit > 0)
    <div class="sc-decline-warn">
        <div class="sc-decline-warn-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
        </div>
        <div>
            <div class="sc-decline-warn-title">تراجع عن بداية الحملة — خسر {{ number_format($agent->true_deficit) }} خط</div>

        </div>
    </div>
@endif
