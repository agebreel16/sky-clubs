<x-filament-widgets::widget>

    {{-- Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="var(--sc-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
            </svg>
            <span style="font-size: 15px; font-weight: 700; color: var(--sc-text);">
              رحلة الوكلاء في الحملة
            </span>
            <span style="font-size: 11px; color: var(--sc-text3); font-weight: 500;">
              تتبع الوكلاء في مراحل الحملة المختلفة
            </span>
        </div>
        <span style="font-size: 12px; color: var(--sc-text3);">
            الإجمالي: <strong style="color: var(--sc-text);">{{ number_format($total) }}</strong> وكيل
        </span>
    </div>

    {{-- Stages Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
        @foreach($stages as $index => $stage)
            @php
                $pct = $total > 0 ? round(($stage['count'] / $total) * 100) : 0;
            @endphp

            @if($stage['url'])
                <a href="{{ $stage['url'] }}" style="text-decoration: none;">
            @else
                <div>
            @endif

                <div style="padding: 14px 16px; border-radius: var(--sc-radius-sm);
                            background: {{ $stage['bg'] }};
                            border: 1px solid {{ $stage['color'] }}25;
                            transition: transform 0.15s, border-color 0.15s;
                            cursor: {{ $stage['url'] ? 'pointer' : 'default' }};"
                     @if($stage['url'])
                     onmouseover="this.style.transform='translateY(-2px)'; this.style.borderColor='{{ $stage['color'] }}60';"
                     onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='{{ $stage['color'] }}25';"
                     @endif>

                    {{-- Arrow connector (not for first) --}}
                    @if($index > 0 && $index < 4)
                        <div style="position: absolute; top: 50%; left: -10px; width: 20px; height: 2px;
                                    background: {{ $stages[$index - 1]['color'] }}25; transform: translateY(-50%);">
                            <div style="position: absolute; top: 50%; left: 100%; width: 0; height: 0;
                                        border-top: 5px solid transparent; border-bottom: 5px solid transparent;
                                        border-left: 10px solid {{ $stages[$index - 1]['color'] }}25;
                                        transform: translateY(-50%);"></div>
                        </div>
                    @endif

                    {{-- Count --}}
                    <div style="font-size: 28px; font-weight: 900; color: {{ $stage['color'] }};
                                line-height: 1; font-variant-numeric: tabular-nums; margin-bottom: 4px;">
                        {{ number_format($stage['count']) }}
                    </div>

                    {{-- Label --}}
                    <div style="font-size: 12px; font-weight: 600; color: var(--sc-text2); margin-bottom: 6px;">
                        {{ $stage['label'] }}
                    </div>

                    {{-- Percentage bar --}}
                    <div style="height: 3px; background: var(--sc-surface3); border-radius: 99px; overflow: hidden;">
                        <div style="height: 100%; width: {{ $pct }}%; background: {{ $stage['color'] }};
                                    border-radius: 99px; transition: width 0.6s ease;"></div>
                    </div>
                    <div style="font-size: 10px; color: var(--sc-text3); margin-top: 4px; text-align: left;">
                        {{ $pct }}%
                    </div>
                </div>

            @if($stage['url'])
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>


</x-filament-widgets::widget>
