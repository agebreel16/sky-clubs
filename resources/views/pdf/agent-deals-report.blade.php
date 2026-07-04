<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="utf-8">
<style>
    body {
        font-family: "Cairo", sans-serif;
        direction: rtl;
        text-align: right;
        font-size: 12px;
        color: #1c2430;
    }

    /* يمنع خوارزمية bidi من إعادة ترتيب أجزاء التواريخ/الأرقام (وإشارة السالب)
       عند تضمينها داخل نص عربي RTL */
    .ltr { direction: ltr; unicode-bidi: embed; display: inline-block; }

    .section-title {
        margin: 0 0 10px;
        font-size: 15px;
        font-weight: 800;
        color: #14294f;
    }
    .section-title .bar {
        display: inline-block;
        width: 4px;
        height: 16px;
        background: #c8912b;
        border-radius: 2px;
        vertical-align: middle;
        margin-left: 8px;
    }

    table.cards { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin-bottom: 26px; }
    table.cards td { vertical-align: top; border-radius: 12px; padding: 18px 20px; }

    .card-total { background: #14294f; color: #fff; }
    .card-total .label { font-size: 12.5px; color: #b9c4dc; font-weight: 600; margin-bottom: 8px; }
    .card-total .value { font-size: 48px; font-weight: 900; line-height: 1; }

    .card-plain { background: #f4f5f8; border: 1px solid #e4e7ee; }
    .card-plain .label { font-size: 12.5px; color: #5b6472; font-weight: 600; margin-bottom: 8px; }
    .card-plain .value { font-size: 38px; font-weight: 900; line-height: 1; color: #14294f; }

    .card-plain.failed { background: #fdecea; border: 1px solid #f3c2bc; }
    .card-plain.failed .value { font-size: 20px; color: #dc2626; }

    table.kpis { width: 100%; border-collapse: separate; border-spacing: 7px 0; margin-bottom: 22px; }
    table.kpis td { width: 25%; vertical-align: top; text-align: center; background: #fff; border: 1px solid #e4e7ee; border-radius: 12px; padding: 18px 14px; }
    table.kpis td.highlight { border: 1.5px solid #14294f; }
    table.kpis .label { font-size: 12px; color: #5b6472; font-weight: 600; margin-bottom: 8px; }
    table.kpis .value { font-size: 40px; font-weight: 900; line-height: 1; color: #14294f; }
    table.kpis .value.new { color: #1a7a4c; }
    table.kpis .value.transfer { color: #8a93a3; }
    table.kpis .value.sub { font-size: 18px; color: #c8912b; font-weight: 700; }

    table.report { width: 100%; border-collapse: collapse; font-size: 13px; }
    table.report thead th {
        background: #14294f;
        color: #fff;
        font-weight: 700;
        text-align: center;
        padding: 10px 12px;
        border: 1px solid #14294f;
    }
    table.report tbody td {
        text-align: center;
        padding: 8px 12px;
        border: 1px solid #e4e7ee;
        font-variant-numeric: tabular-nums;
    }
    table.report tbody tr.odd { background: #ffffff; }
    table.report tbody tr.even { background: #fafbfc; }

    .num-cell { font-size: 18px; }
    .num-zero { font-weight: 500; color: #1c2430; }
    .num-pos-new, .num-pos-total { font-weight: 800; color: #1a7a4c; }
    .num-pos-transfer { font-weight: 800; color: #2169E9; }
    .num-neg { font-weight: 800; color: #dc2626; }
    .num-cumulative { font-weight: 800; color: #14294f; }
    .num-deactivated { font-weight: 800; color: #b45309; }
    .num-muted { color: #8a93a3; }

    .pill { font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 20px; }
    .pill-ok { background: #eaf6ee; color: #1a7a4c; }
    .pill-bad { background: #fdecea; color: #dc2626; }
    .pill-muted { background: #f4f5f8; color: #8a93a3; }
</style>
</head>
<body>

<table style="width:100%; border-collapse:collapse; margin-bottom:14px;">
    <tr>
        <td style="text-align:right; vertical-align:top;">
            <div style="font-size:25px; font-weight:600; letter-spacing:0.5px; color:#2169E9; text-transform:uppercase;">
                تقرير أرقام الوكيل خلال فترة محددة
            </div>
            <div style="font-size:14px; color:#5b6472; font-weight:500; margin-top:6px;">
                الوكيل: <span style="font-weight:800; color:#1c2430;">{{ $agent->agent_name }}@if($agent->phone) — <span class="ltr">{{ $agent->phone }}</span>@endif</span>
            </div>
        </td>
        <td style="text-align:right; vertical-align:top; width:220px; font-size:12.5px; color:#5b6472; line-height:1.9; padding-right:24px;">
            <div>الفترة: <strong class="ltr" style="color:#1c2430;">{{ $periodFrom }} إلى {{ $periodUntil }}</strong></div>
            <div>تاريخ التصدير: <strong class="ltr" style="color:#1c2430;">{{ $generatedAt }}</strong></div>
            @if($generatedBy)
                <div>بواسطة: <strong style="color:#1c2430;">{{ $generatedBy }}</strong></div>
            @endif
        </td>
    </tr>
</table>
<div style="border-bottom:3px solid #14294f; margin-bottom:22px;"></div>

<div class="section-title"><span class="bar"></span>الإجمالي منذ البداية</div>
<table class="cards">
    <tr>
        <td class="card-total" style="width:40%;">
            <div class="label">الإجمالي الكلي (منذ البداية)</div>
            <div class="value">
                @if(!$preCampaignFailed && !$postCampaignFailed)
                    <span class="ltr">{{ number_format($preCampaignLineCount + $postCampaignLineCount) }}</span>
                @else
                    —
                @endif
            </div>
        </td>
        <td class="card-plain {{ $preCampaignFailed ? 'failed' : '' }}" style="width:30%;">
            <div class="label">عدد الخطوط حتى <span class="ltr">{{ $campaignStartLabel }}</span></div>
            <div class="value">{{ $preCampaignFailed ? 'تعذر الجلب' : '' }}<span class="ltr">{{ $preCampaignFailed ? '' : number_format($preCampaignLineCount) }}</span></div>
        </td>
        <td class="card-plain {{ $postCampaignFailed ? 'failed' : '' }}" style="width:30%;">
            <div class="label">عدد الخطوط من <span class="ltr">{{ $campaignStartLabel }}</span> حتى اليوم</div>
            <div class="value">{{ $postCampaignFailed ? 'تعذر الجلب' : '' }}<span class="ltr">{{ $postCampaignFailed ? '' : number_format($postCampaignLineCount) }}</span></div>
        </td>
    </tr>
</table>

@php
    $okRows = collect($rows)->where('ok', true)->where('status', '!=', 'قبل بداية الحملة');
    $totalNew = $okRows->sum('daily_new');
    $totalTransfer = $okRows->sum('daily_transfer');
    $totalOverall = $okRows->sum('daily_total');
@endphp

<div class="section-title"><span class="bar"></span>ملخص الفترة المختارة</div>
<table class="kpis">
    <tr>
        <td class="highlight">
            <div class="label">الإجمالي الكلي للفترة</div>
            <div class="value"><span class="ltr">{{ number_format($totalOverall) }}</span></div>
        </td>
        <td>
            <div class="label">إجمالي أرقام جديدة</div>
            <div class="value new"><span class="ltr">{{ number_format($totalNew) }}</span></div>
        </td>
        <td>
            <div class="label">إجمالي تحويل</div>
            <div class="value transfer"><span class="ltr">{{ number_format($totalTransfer) }}</span></div>
        </td>
        <td>
            <div class="label">عدد الأيام / غير مكتملة</div>
            <div class="value"><span class="ltr">{{ count($rows) }}</span><span class="sub"> / {{ $incompleteDaysCount }}</span></div>
        </td>
    </tr>
</table>

<div class="section-title"><span class="bar"></span>التفصيل اليومي</div>
<table class="report">
    <thead>
        <tr>
            <th>التاريخ</th>
            <th>خطوط جديدة</th>
            <th>خطوط تحويل</th>
            <th>الإجمالي يومي</th>
            <th>الإجمالي التراكمي</th>
            <th>الملغى</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($rows as $i => $row)
            @php
                $new = $row['daily_new'];
                $transfer = $row['daily_transfer'];
                $total = $row['daily_total'];

                $deactivated = $row['daily_deactivated'] ?? null;

                $newClass = $new === null ? '' : ($new < 0 ? 'num-neg' : ($new > 0 ? 'num-pos-new' : 'num-zero'));
                $transferClass = $transfer === null ? '' : ($transfer < 0 ? 'num-neg' : ($transfer > 0 ? 'num-pos-transfer' : 'num-muted'));
                $totalClass = $total === null ? '' : ($total < 0 ? 'num-neg' : ($total > 0 ? 'num-pos-total' : 'num-zero'));
                $deactivatedClass = $deactivated === null ? '' : ($deactivated > 0 ? 'num-deactivated' : 'num-muted');
            @endphp
            <tr class="{{ $i % 2 === 0 ? 'odd' : 'even' }}" style="break-inside:avoid;">
                <td><span class="ltr">{{ $row['date'] }}</span></td>
                <td class="num-cell {{ $newClass }}"><span class="ltr">{{ $new ?? '—' }}</span></td>
                <td class="num-cell {{ $transferClass }}"><span class="ltr">{{ $transfer ?? '—' }}</span></td>
                <td class="num-cell {{ $totalClass }}"><span class="ltr">{{ $total ?? '—' }}</span></td>
                <td class="num-cell num-cumulative"><span class="ltr">{{ $row['cumulative_total'] ?? '—' }}</span></td>
                <td class="num-cell {{ $deactivatedClass }}"><span class="ltr">{{ $deactivated ?? '—' }}</span></td>
                <td>
                    @if($row['status'] === 'تم')
                        <span class="pill pill-ok">تم</span>
                    @elseif($row['status'] === 'قبل بداية الحملة')
                        <span class="pill pill-muted">قبل بداية الحملة</span>
                    @else
                        <span class="pill pill-bad">غير مكتمل</span>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

</body>
</html>
