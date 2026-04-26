<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">حالة الأندية</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($clubs as $item)
                @php
                    $club         = $item['club'];
                    $membersCount = $item['membersCount'];
                    $percentage   = $item['percentage'];
                    $status       = $item['status'];
                    $latest       = $item['latestMember'];

                    $colorClass = $status === 'EXCEEDING' ? 'emerald' : 'sky';
                    $badgeColor = $status === 'EXCEEDING' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-sky-100 text-sky-800 dark:bg-sky-900 dark:text-sky-200';
                    $barColor   = $percentage >= 100 ? 'bg-emerald-500' : ($percentage >= 70 ? 'bg-amber-500' : 'bg-sky-500');
                @endphp

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 flex flex-col gap-4 shadow-sm">

                    {{-- Club Header --}}
                    <div class="flex items-center justify-between">
                        <span class="text-base font-bold text-gray-800 dark:text-gray-100">{{ $club->club_name }}</span>
                        <span class="text-xs font-medium px-2 py-1 rounded-full {{ $badgeColor }}">
                            {{ $status === 'EXCEEDING' ? 'يتجاوز الهدف 🎉' : 'يلبّي الحد الأدنى' }}
                        </span>
                    </div>

                    {{-- Members Count --}}
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">عدد الأعضاء</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($membersCount) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500 dark:text-gray-400">الحد الأدنى</p>
                            <p class="text-lg font-semibold text-gray-600 dark:text-gray-300">{{ number_format($club->seat_capacity) }}</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>نسبة الإشغال</span>
                            <span class="font-bold">{{ $percentage }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                            <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-500"
                                 style="width: {{ min($percentage, 100) }}%"></div>
                        </div>
                    </div>

                    {{-- Latest Member --}}
                    @if($latest)
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400">آخر عضو</p>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $latest->agent_name }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $latest->entry_date ? $latest->entry_date->format('d/m/Y') : $latest->created_at->format('d/m/Y') }}
                            </p>
                        </div>
                    @else
                        <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                            <p class="text-xs text-gray-400 dark:text-gray-500">لا يوجد أعضاء بعد</p>
                        </div>
                    @endif

                    {{-- View Members Button --}}
                    <a href="{{ url('/admin/agents?tableFilters[current_club_id][value]=' . $club->club_id) }}"
                       class="inline-flex items-center justify-center gap-1 w-full rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <x-heroicon-m-users class="w-3.5 h-3.5"/>
                        عرض الأعضاء
                    </a>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
