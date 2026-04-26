<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Club Status Overview</x-slot>
        <x-slot name="description">Current members vs. minimum required to unlock lottery draw</x-slot>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            @foreach($this->getClubData() as $club)
                @php
                    $percent = $club['min_required'] > 0
                        ? min(100, round(($club['members'] / $club['min_required']) * 100))
                        : 100;
                    $colorClass = match(true) {
                        $club['lottery_ready'] => 'bg-emerald-500',
                        $percent >= 75         => 'bg-amber-400',
                        default                => 'bg-rose-400',
                    };
                    $badgeClass = $club['lottery_ready']
                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'
                        : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200';
                @endphp

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $club['name'] }}
                        </h3>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
                            {{ $club['lottery_ready'] ? '🎰 Lottery Ready' : '⏳ Building' }}
                        </span>
                    </div>

                    <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <div class="flex justify-between">
                            <span>Members</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($club['members']) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Min for Draw</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ number_format($club['min_required']) }}</span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-1 text-xs text-gray-500">
                            <span>Progress</span>
                            <span class="font-semibold">{{ $percent }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-2 rounded-full {{ $colorClass }} transition-all duration-500"
                                 style="width: {{ $percent }}%"></div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-2 text-center">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">₪{{ $club['base_reward'] }}</div>
                            <div>Entry Reward</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700/50 p-2 text-center">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">₪{{ $club['prize'] }}</div>
                            <div>Grand Prize</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
