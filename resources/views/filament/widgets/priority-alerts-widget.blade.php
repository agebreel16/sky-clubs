<x-filament-widgets::widget>
    @if(count($alerts) > 0)
        <div style="display: flex; flex-direction: column; gap: 10px; padding: 4px 0;">

            {{-- Header --}}
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="var(--sc-red)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span style="font-size: 13px; font-weight: 700; color: var(--sc-text); letter-spacing: 0.3px;">
                    تنبيهات تحتاج تدخلاً
                </span>
            </div>

            @foreach($alerts as $alert)
                @php
                    $color = $alert['severity'] === 'danger' ? 'var(--sc-red)' : 'var(--sc-orange)';
                    $bg    = $alert['severity'] === 'danger'
                        ? 'rgba(180, 50, 50, 0.08)'
                        : 'rgba(180, 120, 30, 0.08)';
                @endphp

                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px;
                            padding: 12px 16px; border-radius: var(--sc-radius-sm);
                            border: 1px solid {{ $color }}30;
                            background: {{ $bg }};">

                    <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 0;">
                        {{-- Icon --}}
                        <div style="flex-shrink: 0; width: 32px; height: 32px; border-radius: 8px;
                                    background: {{ $color }}18;
                                    display: flex; align-items: center; justify-content: center;">
                            @if($alert['icon'] === 'clock')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="{{ $color }}" stroke-width="2.5" stroke-linecap="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <polyline points="12 6 12 12 16 14"/>
                                </svg>
                            @elseif($alert['icon'] === 'x-circle')
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="{{ $color }}" stroke-width="2.5" stroke-linecap="round">
                                    <circle cx="12" cy="12" r="10"/>
                                    <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                </svg>
                            @else
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                     stroke="{{ $color }}" stroke-width="2.5" stroke-linecap="round">
                                    <line x1="12" y1="1" x2="12" y2="23"/>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                </svg>
                            @endif
                        </div>

                        {{-- Message --}}
                        <span style="font-size: 13px; color: var(--sc-text2); font-weight: 500;">
                            {{ $alert['message'] }}
                        </span>
                    </div>

                    {{-- CTA --}}
                    <a href="{{ $alert['url'] }}"
                       style="flex-shrink: 0; font-size: 12px; font-weight: 700;
                              color: {{ $color }};
                              border: 1px solid {{ $color }}50;
                              padding: 5px 12px; border-radius: 6px;
                              text-decoration: none; white-space: nowrap;
                              transition: background 0.15s;"
                       onmouseover="this.style.background='{{ $color }}18'"
                       onmouseout="this.style.background='transparent'">
                        {{ $alert['cta'] }} ←
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-widgets::widget>
