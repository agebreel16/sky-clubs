<x-filament-widgets::widget>
    <div class="sc-stats-grid px-1 pt-1">

        @foreach($items as $item)
            <div class="sc-stat-card" style="--sc-c: {{ $item['color'] }}">
                <div class="sc-stat-icon">
                    @if($item['icon'] === 'up')
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                    @elseif($item['icon'] === 'user')
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    @elseif($item['icon'] === 'import')
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    @else
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="23 4 23 10 17 10"/>
                            <polyline points="1 20 1 14 7 14"/>
                            <path d="M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15"/>
                        </svg>
                    @endif
                </div>
                <div class="sc-stat-content">
                    <div class="sc-stat-num">{{ number_format($item['value']) }}</div>
                    <div class="sc-stat-lbl">{{ $item['label'] }}</div>
                    <div style="font-size:10px; color: {{ $item['color'] }}; margin-top:3px; font-weight:600;">
                        {{ $item['diff_text'] }}
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</x-filament-widgets::widget>
