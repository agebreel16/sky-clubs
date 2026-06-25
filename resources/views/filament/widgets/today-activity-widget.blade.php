<x-filament-widgets::widget>
    <div class="sc-stats-grid px-1 pt-1">

        {{-- ترقيات --}}
        <div class="sc-stat-card" style="--sc-c: var(--sc-green)">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                    <polyline points="17 6 23 6 23 12"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ $promotions }}</div>
                <div class="sc-stat-lbl">ترقيات اليوم</div>
                <div style="font-size:10px; color: var(--sc-text3); margin-top:3px; font-weight:500;">
                    وكلاء انتقلوا لنادٍ أعلى
                </div>
            </div>
        </div>

        {{-- تهبيطات --}}
        <div class="sc-stat-card" style="--sc-c: var(--sc-red)">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/>
                    <polyline points="17 18 23 18 23 12"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ $demotions }}</div>
                <div class="sc-stat-lbl">تهبيطات اليوم</div>
                <div style="font-size:10px; color: var(--sc-text3); margin-top:3px; font-weight:500;">
                    وكلاء هبطوا لنادٍ أدنى
                </div>
            </div>
        </div>

        {{-- أوائل جدد --}}
        <div class="sc-stat-card" style="--sc-c: var(--sc-gold)">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ $firstArrivals }}</div>
                <div class="sc-stat-lbl">أوائل جدد</div>
                <div style="font-size:10px; color: var(--sc-text3); margin-top:3px; font-weight:500;">
                    أوائل الداخلين لنادٍ اليوم
                </div>
            </div>
        </div>

        {{-- طلبات اليوم --}}
        <div class="sc-stat-card" style="--sc-c: {{ $pendingToday > 0 ? 'var(--sc-orange)' : 'var(--sc-text3)' }}">
            <div class="sc-stat-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <div class="sc-stat-content">
                <div class="sc-stat-num">{{ $pendingToday }}</div>
                <div class="sc-stat-lbl">طلبات جديدة اليوم</div>
                <div style="font-size:10px; color: var(--sc-text3); margin-top:3px; font-weight:500;">
                    طلبات تغيير النادي المعلّقة
                </div>
            </div>
        </div>

    </div>
</x-filament-widgets::widget>
