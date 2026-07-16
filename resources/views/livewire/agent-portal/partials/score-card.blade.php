{{-- بطاقة نقاط شروط النادي — تُستخدم لكل من "المحافظة على النادي الحالي" و"الهدف القادم".
     تستقبل: $score (ناتج $scoreFor)، $eyebrow، $metText، $unmetText، $variant ('maintain' أو 'next') --}}
@php
    $isFullyMet = $score['incMet'] && $score['transMet'];
    $state = $isFullyMet ? 'success' : ($variant === 'maintain' ? 'warning' : 'active');
    $stateVar = $state === 'success' ? 'success' : ($state === 'active' ? 'primary' : 'warning');
    $rows = [
        ['label' => 'الخطوط داخل الحملة', 'val' => $score['incVal'],   'req' => $score['reqInc'],   'met' => $score['incMet'],   'show' => true],
        ['label' => 'خطوط التحويل',       'val' => $score['transVal'], 'req' => $score['reqTrans'], 'met' => $score['transMet'], 'show' => $score['showTrans']],
    ];
@endphp
<div class="card score-card score-card--{{ $state }} @if($variant === 'next') score-card--priority @endif">
    <div class="score-head">
        <div>
            <div class="score-eyebrow">{{ $eyebrow }}</div>
            <div class="score-club">{{ $score['club']->club_name }}</div>
        </div>
        <div class="score-ring" style="background:conic-gradient(var(--{{ $stateVar }}) {{ $score['pct'] }}%, var(--slate-200) 0);">
            <div class="score-ring-inner">
                <div class="score-ring-num js-countup" style="color:var(--{{ $stateVar }});">{{ number_format($score['totalAchieved']) }}</div>
                <div class="score-ring-den">/{{ number_format($score['totalRequired']) }} خط</div>
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
                        <span class="score-badge {{ $row['met'] ? 'score-badge--done' : 'score-badge--warn' }}">
                            {{ $row['met'] ? '✓ مكتمل' : 'متبقي ' . number_format(max(0, $row['req'] - $row['val'])) }}
                        </span>
                    </div>
                    <div class="score-row-val">{{ number_format($row['val']) }} / {{ number_format($row['req']) }}</div>
                    <div class="score-row-bar">
                        <div class="score-row-fill" style="background:{{ $row['met'] ? 'var(--success)' : 'var(--warning)' }};" data-fill-width="{{ $rowPct }}%"></div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="score-foot {{ $isFullyMet ? 'score-foot--done' : 'score-foot--warn' }}">
        {{ $isFullyMet ? $metText : $unmetText }}
    </div>
</div>
