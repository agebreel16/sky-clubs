<x-filament-widgets::widget>
    <div class="sky-premium-container" style="direction: rtl; font-family: 'Rubik', sans-serif;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
            <h2 style="font-size: 1.5rem; font-weight: 800; color: #111827; display: flex; align-items: center; gap: 0.75rem;">
                <span style="padding: 0.5rem; background: rgba(var(--primary-500), 0.1); border-radius: 0.5rem; color: rgb(var(--primary-600)); display: flex;">
                    <x-heroicon-o-flag style="width: 1.5rem; height: 1.5rem;"/>
                </span>
                حالة الأندية
            </h2>
            <div style="font-size: 0.875rem; color: #6b7280;">
                تحديث مباشر لتوزيع الوكلاء
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            @foreach($clubs as $item)
                @php
                    $club         = $item['club'];
                    $membersCount = $item['membersCount'];
                    $percentage   = $item['percentage'];
                    $status       = $item['status'];
                    $latest       = $item['latestMember'];
                    $meta         = $item['metadata'];
                    $isFull       = $membersCount >= $club->seat_capacity;

                    // Manual CSS Gradients based on metadata
                    $gradientCss = match($club->club_name) {
                        'Launch Club'     => 'linear-gradient(135deg, #2563eb 0%, #38bdf8 100%)',
                        'Excellence Club' => 'linear-gradient(135deg, #d97706 0%, #fbbf24 100%)',
                        'Peak Club'       => 'linear-gradient(135deg, #4338ca 0%, #8b5cf6 100%)',
                        default           => 'linear-gradient(135deg, #4b5563 0%, #9ca3af 100%)',
                    };
                    $shadowColor = match($club->club_name) {
                        'Launch Club'     => 'rgba(37, 99, 235, 0.2)',
                        'Excellence Club' => 'rgba(217, 119, 6, 0.2)',
                        'Peak Club'       => 'rgba(67, 56, 202, 0.2)',
                        default           => 'rgba(75, 85, 99, 0.2)',
                    };
                    $textColor = match($club->club_name) {
                        'Launch Club'     => '#2563eb',
                        'Excellence Club' => '#d97706',
                        'Peak Club'       => '#4338ca',
                        default           => '#4b5563',
                    };
                @endphp

                <div class="sky-club-card" style="position: relative; overflow: hidden; border-radius: 1.25rem; background: #ffffff; border: 1px solid #f3f4f6; box-shadow: 0 20px 25px -5px {{ $shadowColor }}; transition: all 0.3s ease; height: 100%;">
                    {{-- Decorative Blur --}}
                    <div style="position: absolute; top: -2rem; right: -2rem; width: 8rem; height: 8rem; background: {{ $gradientCss }}; opacity: 0.05; border-radius: 50%; filter: blur(40px);"></div>

                    <div style="padding: 1.5rem; position: relative; display: flex; flex-direction: column; height: 100%;">
                        {{-- Header --}}
                        <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem;">
                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="padding: 0.75rem; border-radius: 0.75rem; background: {{ $gradientCss }}; color: #ffffff; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); display: flex;">
                                    <x-dynamic-component :component="$meta['icon']" style="width: 1.5rem; height: 1.5rem;"/>
                                </div>
                                <div>
                                    <h3 style="font-size: 1.125rem; font-weight: 700; color: #111827; margin: 0;">{{ $club->club_name }}</h3>
                                    <span style="font-size: 0.75rem; font-weight: 500; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;">مرتبة : {{ $club->club_order }}</span>
                                </div>
                            </div>

                            <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                @if($isFull)
                                    <span style="display: flex; align-items: center; gap: 0.375rem; padding: 0.25rem 0.625rem; border-radius: 9999px; background: #ecfdf5; color: #065f46; font-size: 0.625rem; font-weight: 700; text-transform: uppercase;">
                                        <span class="pulse-dot" style="width: 0.375rem; height: 0.375rem; border-radius: 50%; background: #10b981;"></span>
                                        مكتمل
                                    </span>
                                @else
                                    <span style="padding: 0.25rem 0.625rem; border-radius: 9999px; background: #f3f4f6; color: #4b5563; font-size: 0.625rem; font-weight: 700; text-transform: uppercase;">
                                        جاري الملء
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                            <div style="display: flex; flex-direction: column;">
                                <p style="font-size: 2rem; font-weight: 900; color: #111827; margin: 0; line-height: 1;">{{ number_format($membersCount) }}</p>
                                <p style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin: 0.25rem 0 0 0;">وكيل حالي</p>
                            </div>
                            <div style="text-align: right;">
                                <p style="font-size: 1.25rem; font-weight: 700; color: #9ca3af; margin: 0; line-height: 1;">/ {{ number_format($club->seat_capacity) }}</p>
                                <p style="font-size: 0.75rem; font-weight: 500; color: #6b7280; margin: 0.25rem 0 0 0;">الحد الأدنى</p>
                            </div>
                        </div>

                        {{-- Progress --}}
                        <div style="margin-bottom: 1.5rem; flex-grow: 1;">
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">
                                <span style="color: {{ $textColor }}">نسبة الإشغال</span>
                                <span style="color: #111827;">{{ $percentage }}%</span>
                            </div>
                            <div style="position: relative; height: 0.75rem; width: 100%; background: #f3f4f6; border-radius: 9999px; overflow: hidden;">
                                <div style="position: absolute; top: 0; left: 0; height: 100%; background: {{ $gradientCss }}; border-radius: 9999px; width: {{ min($percentage, 100) }}%; transition: width 1s ease-out;">
                                    <div class="shimmer-effect"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Footer --}}
                        <div style="padding-top: 1rem; border-top: 1px solid #f9fafb; display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; margin-left: 0.5rem;">
                                <div style="height: 2rem; width: 2rem; border-radius: 50%; border: 2px solid #ffffff; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                    <x-heroicon-m-user-circle style="width: 1.25rem; height: 1.25rem; color: #9ca3af;"/>
                                </div>
                                <div style="height: 2rem; width: 2rem; border-radius: 50%; border: 2px solid #ffffff; background: #f9fafb; display: flex; align-items: center; justify-content: center; margin-right: -0.5rem; font-size: 0.625rem; font-weight: 700; color: #6b7280;">
                                    +{{ max(0, $membersCount - 1) }}
                                </div>
                            </div>

                            @if($latest)
                                <div style="text-align: left;">
                                    <p style="font-size: 0.625rem; color: #9ca3af; font-weight: 500; margin: 0;">آخر انضمام</p>
                                    <p style="font-size: 0.75rem; font-weight: 700; color: #374151; margin: 0;">{{ Str::limit($latest->agent_name, 15) }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Button --}}
                        <a href="{{ url('/admin/agents?tableFilters[current_club_id][value]=' . $club->club_id) }}"
                           class="premium-btn"
                           style="margin-top: 1rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem; width: 100%; padding: 0.625rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: transparent; font-size: 0.875rem; font-weight: 700; color: #374151; text-decoration: none; transition: all 0.2s ease;">
                            عرض التفاصيل
                            <x-heroicon-m-arrow-left style="width: 1rem; height: 1rem;"/>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <style>
        .sky-club-card:hover { transform: translateY(-4px); }
        .pulse-dot { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }
        .shimmer-effect {
            position: absolute; top: 0; right: 0; h: 100%; width: 40px; background: rgba(255,255,255,0.2);
            transform: skewX(-12deg); animation: shimmer 2s infinite;
        }
        @keyframes shimmer { 0% { transform: translateX(150%) skewX(-12deg); } 100% { transform: translateX(-300%) skewX(-12deg); } }
        .premium-btn:hover { background: #f9fafb; border-color: #d1d5db; }
    </style>
</x-filament-widgets::widget>

