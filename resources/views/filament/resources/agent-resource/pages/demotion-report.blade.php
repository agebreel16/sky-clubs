<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                قائمة الوكلاء في مرحلة التحذير
            </x-slot>
            <x-slot name="description">
                يعرض هذا التقرير جميع الوكلاء الذين بدأ لديهم عداد التهبيط ولم يحققوا شروط المحافظة بعد.
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
