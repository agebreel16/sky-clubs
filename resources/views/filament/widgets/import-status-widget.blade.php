<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">آخر استيراد بيانات</x-slot>

        @if($import)
            @php
                $statusColors = [
                    'success'    => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200',
                    'failed'     => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                    'pending'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                ];
                $statusLabels = [
                    'success'    => 'نجح ✓',
                    'failed'     => 'فشل ✗',
                    'processing' => 'جارٍ المعالجة...',
                    'pending'    => 'في الانتظار',
                ];
                $statusColor = $statusColors[$import->status] ?? 'bg-gray-100 text-gray-800';
                $statusLabel = $statusLabels[$import->status] ?? $import->status;
            @endphp

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 flex-1">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">تاريخ الاستيراد</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $import->data_date ? $import->data_date->format('d/m/Y') : 'غير محدد' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">المصدر</p>
                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full
                            {{ $import->source_type === 'excel' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                            {{ strtoupper($import->source_type) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">الحالة</p>
                        <span class="inline-block text-xs font-medium px-2 py-0.5 rounded-full {{ $statusColor }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">تمت المعالجة</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ number_format($import->total_agents ?? 0) }} وكيل
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="text-center rounded-lg bg-emerald-50 dark:bg-emerald-900/30 px-3 py-2">
                        <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($import->processed ?? 0) }}</p>
                        <p class="text-xs text-emerald-600 dark:text-emerald-400">مقبول</p>
                    </div>
                    <div class="text-center rounded-lg bg-red-50 dark:bg-red-900/30 px-3 py-2">
                        <p class="text-lg font-bold text-red-700 dark:text-red-300">{{ number_format($import->rejected ?? 0) }}</p>
                        <p class="text-xs text-red-600 dark:text-red-400">مرفوض</p>
                    </div>
                    <div class="text-center rounded-lg bg-sky-50 dark:bg-sky-900/30 px-3 py-2">
                        <p class="text-lg font-bold text-sky-700 dark:text-sky-300">{{ number_format($import->promotions_count ?? 0) }}</p>
                        <p class="text-xs text-sky-600 dark:text-sky-400">ترقيات</p>
                    </div>
                </div>

                <div class="flex flex-col gap-2 shrink-0">
                    <a href="{{ url('/admin/data-imports/' . $import->import_id) }}"
                       class="inline-flex items-center justify-center gap-1 rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        <x-heroicon-m-eye class="w-3.5 h-3.5"/>
                        عرض التفاصيل
                    </a>
                    <a href="{{ url('/admin/data-imports/create') }}"
                       class="inline-flex items-center justify-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-700 transition">
                        <x-heroicon-m-arrow-up-tray class="w-3.5 h-3.5"/>
                        رفع ملف جديد
                    </a>
                </div>
            </div>

            @if($import->error_message)
                <div class="mt-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 px-4 py-2 text-sm text-red-700 dark:text-red-300">
                    <strong>خطأ:</strong> {{ $import->error_message }}
                </div>
            @endif
        @else
            <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-arrow-up-tray class="w-10 h-10 mb-2"/>
                <p class="text-sm">لا يوجد استيراد بعد</p>
                <a href="{{ url('/admin/data-imports/create') }}"
                   class="mt-3 inline-flex items-center gap-1 rounded-lg bg-primary-600 px-4 py-2 text-xs font-medium text-white hover:bg-primary-700 transition">
                    <x-heroicon-m-arrow-up-tray class="w-3.5 h-3.5"/>
                    رفع أول ملف
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
