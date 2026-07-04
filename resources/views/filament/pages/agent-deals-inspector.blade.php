<x-filament-panels::page>

@php
    $rows = $this->reportRows;
    $okRows = collect($rows)->where('ok', true)->where('status', '!=', 'قبل بداية الحملة');

    $totalNew      = $okRows->sum('daily_new');
    $totalTransfer = $okRows->sum('daily_transfer');
    $totalOverall  = $okRows->sum('daily_total');

    $maxAbs = collect($rows)->pluck('daily_total')->filter(fn ($v) => $v !== null)->map(fn ($v) => abs($v))->max();
    $maxAbs = $maxAbs > 0 ? $maxAbs : 1;

    $halfHeight = 90;
@endphp

{{-- ── Hero Banner ── --}}
<div style="background:linear-gradient(135deg, #0f2460 0%, #1e3a8a 40%, #2563eb 100%);
     border-radius:20px; padding:24px 28px; box-shadow:0 12px 32px rgba(15,36,96,0.35);">
    <p style="margin:0 0 8px; font-size:10.5px; font-weight:700; letter-spacing:2.5px; text-transform:uppercase; color:rgba(147,197,253,0.8);">
        API خطوط الوكلاء
    </p>
    <span style="font-size:22px; font-weight:800; color:#fff; letter-spacing:-.3px;">تقرير مفصل لأرقام وكيل</span>

</div>

{{-- ── Filter Form ── --}}
<div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06); margin-top:20px;">
    <div style="padding:16px 24px; border-bottom:1px solid #f3f4f6; background:#f9fafb;">
        <p style="margin:0; font-size:14px; font-weight:600; color:#111827;">اختيار الوكيل والفترة</p>
    </div>

    <div style="padding:24px;">
        {{ $this->filterForm }}

        <div style="margin-top:20px;">
            <x-filament::button wire:click="fetchReport" wire:loading.attr="disabled" color="primary">
                <span wire:loading.remove wire:target="fetchReport">جلب التقرير</span>
                <span wire:loading wire:target="fetchReport">جارٍ الجلب...</span>
            </x-filament::button>
        </div>
    </div>
</div>

@if (count($rows) > 0)

    {{-- ── قبل/بعد بداية الحملة (إجمالي منذ البداية) ── --}}
    @php
        $lifetimeTotal = (! $this->preCampaignFailed && ! $this->postCampaignFailed)
            ? $this->preCampaignLineCount + $this->postCampaignLineCount
            : null;
    @endphp
    <div style="margin-top:20px; display:grid; grid-template-columns:repeat(3,1fr); gap:12px;">
        <div style="background:{{ $this->preCampaignFailed ? '#fef2f2' : '#fffbeb' }};border:1px solid {{ $this->preCampaignFailed ? '#fecaca' : '#fde68a' }};border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:{{ $this->preCampaignFailed ? '#991b1b' : '#92400e' }};">عدد الخطوط حتى {{ $this->campaignStartLabel }}</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:{{ $this->preCampaignFailed ? '#dc2626' : '#b45309' }};">
                {{ $this->preCampaignFailed ? 'تعذر الجلب' : number_format($this->preCampaignLineCount) }}
            </p>
        </div>
        <div style="background:{{ $this->postCampaignFailed ? '#fef2f2' : '#eff6ff' }};border:1px solid {{ $this->postCampaignFailed ? '#fecaca' : '#bfdbfe' }};border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:{{ $this->postCampaignFailed ? '#991b1b' : '#1e40af' }};">عدد الخطوط من {{ $this->campaignStartLabel }} حتى اليوم</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:{{ $this->postCampaignFailed ? '#dc2626' : '#1e3a8a' }};">
                {{ $this->postCampaignFailed ? 'تعذر الجلب' : number_format($this->postCampaignLineCount) }}
            </p>
        </div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#166534;">الإجمالي الكلي (منذ البداية)</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:#15803d;">
                {{ $lifetimeTotal !== null ? number_format($lifetimeTotal) : '—' }}
            </p>
        </div>
    </div>

    {{-- ── Summary Cards ── --}}
    <div style="margin-top:20px; display:grid; grid-template-columns:repeat(5,1fr); gap:12px;">
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#1e40af;">إجمالي أرقام جديدة</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:#1e3a8a;">{{ number_format($totalNew) }}</p>
        </div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#166534;">إجمالي تحويل</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:#15803d;">{{ number_format($totalTransfer) }}</p>
        </div>
        <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#6b21a8;">الإجمالي الكلي للفترة</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:#7e22ce;">{{ number_format($totalOverall) }}</p>
        </div>
        <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#374151;">عدد الأيام</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:#111827;">{{ count($rows) }}</p>
        </div>
        <div style="background:{{ $this->incompleteDaysCount > 0 ? '#fef2f2' : '#f9fafb' }};border:1px solid {{ $this->incompleteDaysCount > 0 ? '#fecaca' : '#e5e7eb' }};border-radius:12px;padding:14px 16px;">
            <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:{{ $this->incompleteDaysCount > 0 ? '#991b1b' : '#374151' }};">أيام غير مكتملة</p>
            <p style="margin:0;font-size:20px;font-weight:800;color:{{ $this->incompleteDaysCount > 0 ? '#dc2626' : '#111827' }};">{{ $this->incompleteDaysCount }}</p>
        </div>
    </div>

    <div style="margin:16px 0 8px; display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <p style="margin:0; font-size:13px; color:#6b7280;">
            الوكيل: <strong style="color:#111827;">{{ $this->reportAgentLabel }}</strong>
            <span style="margin:0 10px; color:#d1d5db;">|</span>
            الفترة: <strong style="color:#111827;">{{ $this->reportFrom }}</strong> إلى <strong style="color:#111827;">{{ $this->reportUntil }}</strong>
        </p>
        <x-filament::button wire:click="exportPdf" wire:loading.attr="disabled" color="gray" icon="heroicon-o-document-arrow-down">
            <span wire:loading.remove wire:target="exportPdf">تصدير PDF</span>
            <span wire:loading wire:target="exportPdf">جارٍ التصدير...</span>
        </x-filament::button>
    </div>

    {{-- ── Chart ── --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:20px 16px; margin-top:12px; overflow-x:auto;">
        <p style="margin:0 0 14px; font-size:13px; font-weight:600; color:#111827;">مقارنة الإجمالي اليومي بين الأيام</p>
        <div style="display:flex; align-items:flex-end; gap:6px; min-width:max-content; height:{{ $halfHeight * 2 + 40 }}px;">
            @foreach ($rows as $row)
                @php
                    $val = $row['daily_total'];
                    $h = $val !== null ? (int) round((abs($val) / $maxAbs) * $halfHeight) : 0;
                    $color = $val === null ? '#d1d5db' : ($val < 0 ? '#f87171' : '#60a5fa');
                @endphp
                <div style="display:flex; flex-direction:column; align-items:center; width:34px; flex-shrink:0;">
                    <div style="height:{{ $halfHeight }}px; display:flex; align-items:flex-end;">
                        <div title="{{ $val ?? 'غير مكتمل' }}"
                             style="width:20px; height:{{ $val !== null && $val >= 0 ? $h : 0 }}px; background:{{ $color }}; border-radius:3px 3px 0 0;"></div>
                    </div>
                    <div style="height:{{ $halfHeight }}px; display:flex; align-items:flex-start;">
                        <div title="{{ $val ?? 'غير مكتمل' }}"
                             style="width:20px; height:{{ $val !== null && $val < 0 ? $h : 0 }}px; background:{{ $color }}; border-radius:0 0 3px 3px;"></div>
                    </div>
                    <p style="margin:6px 0 0; font-size:9.5px; color:#9ca3af; writing-mode:vertical-rl; transform:rotate(180deg); white-space:nowrap;">
                        {{ \Carbon\Carbon::parse($row['date'])->format('m-d') }}
                    </p>
                </div>
            @endforeach
        </div>
        <p style="margin:8px 0 0; font-size:11px; color:#9ca3af;">
            <span style="display:inline-block;width:9px;height:9px;background:#60a5fa;border-radius:2px;margin-inline-end:4px;"></span> إجمالي يومي موجب
            <span style="display:inline-block;width:9px;height:9px;background:#f87171;border-radius:2px;margin-inline-start:14px;margin-inline-end:4px;"></span> سالب (تراجع/إلغاء)
            <span style="display:inline-block;width:9px;height:9px;background:#d1d5db;border-radius:2px;margin-inline-start:14px;margin-inline-end:4px;"></span> غير مكتمل
        </p>
    </div>

    {{-- ── Table ── --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; margin-top:16px;">
        <table style="width:100%; border-collapse:collapse; font-size:13px;">
            <thead>
                <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">التاريخ</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">خطوط جديدة</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">خطوط تحويل</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">الإجمالي يومي</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">الإجمالي التراكمي</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">الملغى</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">الحالة</th>
                    <th style="padding:10px 16px; text-align:right; color:#374151; font-weight:600;">رد الـ API</th>
                </tr>
            </thead>
            @foreach ($rows as $row)
                <tbody x-data="{ open: false }">
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:9px 16px; color:#111827;">{{ $row['date'] }}</td>
                        <td style="padding:9px 16px; color:#111827;">{{ $row['daily_new'] ?? '—' }}</td>
                        <td style="padding:9px 16px; color:#111827;">{{ $row['daily_transfer'] ?? '—' }}</td>
                        <td style="padding:9px 16px; font-weight:700; color:{{ $row['daily_total'] !== null && $row['daily_total'] < 0 ? '#dc2626' : '#111827' }};">
                            {{ $row['daily_total'] ?? '—' }}
                        </td>
                        <td style="padding:9px 16px; color:#6b7280;">{{ $row['cumulative_total'] ?? '—' }}</td>
                        <td style="padding:9px 16px; color:{{ ($row['daily_deactivated'] ?? 0) > 0 ? '#b45309' : '#9ca3af' }}; font-weight:{{ ($row['daily_deactivated'] ?? 0) > 0 ? '700' : '400' }};">
                            {{ $row['daily_deactivated'] ?? '—' }}
                        </td>
                        <td style="padding:9px 16px;">
                            @if($row['status'] === 'تم')
                                <span style="color:#16a34a; font-weight:600;">✓ تم</span>
                            @elseif($row['status'] === 'قبل بداية الحملة')
                                <span style="color:#9ca3af;">قبل بداية الحملة</span>
                            @else
                                <span style="color:#dc2626; font-weight:600;">غير مكتمل</span>
                            @endif
                        </td>
                        <td style="padding:9px 16px;">
                            @if($row['status'] !== 'قبل بداية الحملة')
                                <button type="button" @click="open = !open"
                                        style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;font-size:12px;cursor:pointer;">
                                    <span x-text="open ? 'إخفاء' : 'عرض'"></span>
                                </button>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>
                    </tr>
                    @if($row['status'] !== 'قبل بداية الحملة')
                        <tr x-show="open" x-cloak style="border-bottom:1px solid #f3f4f6;">
                            <td colspan="8" style="padding:14px 16px; background:#f9fafb;">

                                {{-- التاريخين الفعليين المُرسلين للـ API لهذا الصف (from ثابت = بداية الحملة، to = تاريخ هذا الصف) --}}
                                <p style="margin:0 0 12px; font-size:12px; color:#6b7280;">
                               الطلب 
                                    <strong style="color:#111827; direction:ltr; display:inline-block;">من {{ $this->campaignStartLabel }} إلى {{ $row['date'] }}</strong>
                 
                                </p>

                                {{-- ملخّص مقروء: نوع الخط × الحالة (تشخيصي — لا يدخل بأي حساب) --}}
                             
                                <table style="width:100%; max-width:520px; border-collapse:collapse; font-size:12px; margin-bottom:12px;">
                                    <thead>
                                        <tr>
                                            <th style="padding:6px 10px; text-align:right; color:#6b7280; border-bottom:1px solid #e5e7eb;"></th>
                                            @foreach($this->knownStatuses() as $status)
                                                <th style="padding:6px 10px; text-align:center; color:#6b7280; border-bottom:1px solid #e5e7eb;">{{ $status }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="padding:6px 10px; color:#374151; font-weight:600;">خطوط جديدة</td>
                                            @foreach($this->knownStatuses() as $status)
                                                <td style="padding:6px 10px; text-align:center; color:#111827;">{{ $this->statusCountFor($row['date'], 'new-order', $status) }}</td>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            <td style="padding:6px 10px; color:#374151; font-weight:600;">خطوط تحويل</td>
                                            @foreach($this->knownStatuses() as $status)
                                                <td style="padding:6px 10px; text-align:center; color:#111827;">{{ $this->statusCountFor($row['date'], 'number-portability', $status) }}</td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>

                                @php
                                    $otherNew = $this->otherStatusesFor($row['date'], 'new-order');
                                    $otherTransfer = $this->otherStatusesFor($row['date'], 'number-portability');
                                @endphp
                                @if(count($otherNew) || count($otherTransfer))
                                    <p style="margin:0 0 12px; font-size:12px; color:#b45309; font-weight:600;">
                                        ⚠ حالات إضافية غير معروفة —
                                        @foreach($otherNew as $status => $count) خطوط جديدة/{{ $status }}: {{ $count }} @endforeach
                                        @foreach($otherTransfer as $status => $count) خطوط تحويل/{{ $status }}: {{ $count }} @endforeach
                                    </p>
                                @endif

                                <p style="margin:0 0 6px; font-size:12px; font-weight:700; color:#374151;">الرد الخام (JSON)</p>
                                <pre style="margin:0; direction:ltr; text-align:left; font-size:11px; line-height:1.6; white-space:pre-wrap; word-break:break-all; color:#374151; max-height:320px; overflow:auto;">{{ $this->rawResponseFor($row['date']) ?? 'لا يوجد رد محفوظ لهذا اليوم' }}</pre>
                            </td>
                        </tr>
                    @endif
                </tbody>
            @endforeach
        </table>
    </div>
@endif

</x-filament-panels::page>
