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
