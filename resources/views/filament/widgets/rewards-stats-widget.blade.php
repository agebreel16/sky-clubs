<x-filament-widgets::widget>
    <div class="sc-stats-grid px-1 pt-1" style="grid-template-columns: repeat(3, 1fr);">

        {{-- مكافآت معلّقة --}}
        <div class="sc-stat-card" style="--sc-c: var(--sc-orange)">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ number_format($pendingAmount, 0) }} ₪</div>
                <div class="sc-stat-lbl">مكافآت معلّقة الدفع</div>
                <div style="font-size:10px; color: var(--sc-c); margin-top:3px; font-weight:600;">
                    {{ $pendingCount }} مكافأة بانتظار الدفع
                </div>
            </div>
        </div>

        {{-- إجمالي المدفوع --}}
        <div class="sc-stat-card" style="--sc-c: var(--sc-green)">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ number_format($paidAmount, 0) }} ₪</div>
                <div class="sc-stat-lbl">إجمالي المدفوع</div>
                <div style="font-size:10px; color: var(--sc-text3); margin-top:3px; font-weight:500;">
                    كامل المكافآت المدفوعة
                </div>
            </div>
        </div>

        {{-- هذا الشهر --}}
        <div class="sc-stat-card" style="--sc-c: var(--sc-accent)">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ number_format($monthAmount, 0) }} ₪</div>
                <div class="sc-stat-lbl">مكافآت هذا الشهر</div>
                <div style="font-size:10px; color: var(--sc-c); margin-top:3px; font-weight:600;">
                    {{ $monthCount }} مكافأة أُنشئت هذا الشهر
                </div>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
