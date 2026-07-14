{{-- بطاقة نقاط شروط النادي — تُستخدم لكل من "المحافظة على النادي الحالي" و"الهدف القادم".
     تستقبل: $score (ناتج $scoreFor)، $eyebrow، $metText، $unmetText --}}
@php
    $scoreColor = match(true) {
        $score['total'] >= 100 => '#10b981',
        $score['total'] >= 70  => '#0ea5e9',
        $score['total'] >= 40  => '#f59e0b',
        default                 => '#ef4444',
    };
    $rows = [
        ['label' => 'الخطوط داخل الحملة', 'val' => $score['incVal'],   'req' => $score['reqInc'],   'pts' => $score['incPts'],   'met' => $score['incMet'],   'show' => true],
        ['label' => 'خطوط التحويل',       'val' => $score['transVal'], 'req' => $score['reqTrans'], 'pts' => $score['transPts'], 'met' => $score['transMet'], 'show' => $score['showTrans']],
    ];
@endphp
<div class="card card-pad score-card" style="border-top:3px solid {{ $scoreColor }};">
    <div class="score-head">
        <div>
            <div class="score-eyebrow">{{ $eyebrow }}</div>
            <div class="score-club">{{ $score['club']->club_name }}</div>
        </div>
        <div class="score-ring" style="background:conic-gradient({{ $scoreColor }} {{ $score['total'] }}%, var(--slate-200) 0);">
            <div class="score-ring-inner">
                <div class="score-ring-num" style="color:{{ $scoreColor }};">{{ $score['total'] }}</div>
                <div class="score-ring-den">/100</div>
            </div>
        </div>
    </div>

    <div class="score-rows">
        @foreach($rows as $row)
            @if($row['show'])
                @php $rowPct = $row['req'] > 0 ? min(100, round($row['val'] / $row['req'] * 100)) : 100; @endphp
                <div class="score-row">
                    <div class="score-row-head">
                        <span class="score-row-label">{{ $row['label'] }}</span>
                        <span class="score-row-pts" style="color:{{ $row['met'] ? '#16a34a' : '#ea580c' }};">{{ $row['pts'] }}/50 نقطة</span>
                    </div>
                    <div class="score-row-val">{{ number_format($row['val']) }} / {{ number_format($row['req']) }}</div>
                    <div class="score-row-bar">
                        <div class="score-row-fill" style="width:0%;background:{{ $row['met'] ? '#10b981' : '#f59e0b' }};" data-fill-width="{{ $rowPct }}%"></div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="score-foot" style="color:{{ $score['total'] >= 100 ? '#16a34a' : '#ea580c' }};background:{{ $score['total'] >= 100 ? '#f0fdf4' : '#fff7ed' }};">
        {{ $score['total'] >= 100 ? $metText : $unmetText }}
    </div>
</div>
