<x-filament-widgets::widget>
    <div style="direction: rtl; font-family: 'Rubik', sans-serif;">
        <div style="background: #ffffff; border-radius: 1.25rem; border: 1px solid #f3f4f6; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); overflow: hidden;">
            {{-- Header Line --}}
            <div style="background: #f9fafb; padding: 1rem 1.5rem; border-bottom: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <div style="background: #fff; padding: 0.5rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05); display: flex;">
                        <x-heroicon-o-cloud-arrow-up style="width: 1.25rem; height: 1.25rem; color: #6366f1;"/>
                    </div>
                    <h3 style="font-size: 1rem; font-weight: 800; color: #111827; margin: 0;">آخر استيراد بيانات</h3>
                </div>
                @if($import)
                    <span style="font-size: 0.75rem; color: #6b7280; font-weight: 500;">
                        تاريخ الملف: {{ $import->data_date ? $import->data_date->format('Y/m/d') : '---' }}
                    </span>
                @endif
            </div>

            <div style="padding: 1.5rem;">
                @if($import)
                    @php
                        $statusMeta = match($import->status) {
                            'success'    => ['color' => '#10b981', 'bg' => '#ecfdf5', 'label' => 'نجح مكتمل', 'icon' => 'heroicon-m-check-circle'],
                            'failed'     => ['color' => '#ef4444', 'bg' => '#fef2f2', 'label' => 'فشل الاستيراد', 'icon' => 'heroicon-m-x-circle'],
                            'processing' => ['color' => '#3b82f6', 'bg' => '#eff6ff', 'label' => 'جارٍ المعالجة', 'icon' => 'heroicon-m-arrow-path'],
                            default      => ['color' => '#f59e0b', 'bg' => '#fffbeb', 'label' => 'في الانتظار', 'icon' => 'heroicon-m-clock'],
                        };
                    @endphp

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; align-items: center;">
                        {{-- Status & Source --}}
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: {{ $statusMeta['bg'] }}; color: {{ $statusMeta['color'] }}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <x-dynamic-component :component="$statusMeta['icon']" style="width: 2rem; height: 2rem;"/>
                            </div>
                            <div>
                                <p style="font-size: 0.875rem; font-weight: 800; color: #111827; margin: 0;">{{ $statusMeta['label'] }}</p>
                                <p style="font-size: 0.75rem; color: #6b7280; margin: 0;">مصدر البيانات: <span style="color: #3b82f6; font-weight: 700;">{{ strtoupper($import->source_type) }}</span></p>
                            </div>
                        </div>

                        {{-- Stats Grid --}}
                        <div style="display: flex; align-items: center; gap: 2rem; background: #f8fafc; padding: 1rem; border-radius: 1rem; border: 1px dashed #e2e8f0;">
                            <div style="text-align: center; flex: 1;">
                                <p style="font-size: 1.25rem; font-weight: 900; color: #059669; margin: 0;">{{ number_format($import->processed ?? 0) }}</p>
                                <p style="font-size: 0.625rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 0.25rem;">مقبول</p>
                            </div>
                            <div style="width: 1px; height: 2rem; background: #e2e8f0;"></div>
                            <div style="text-align: center; flex: 1;">
                                <p style="font-size: 1.25rem; font-weight: 900; color: #dc2626; margin: 0;">{{ number_format($import->rejected ?? 0) }}</p>
                                <p style="font-size: 0.625rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 0.25rem;">مرفوض</p>
                            </div>
                            <div style="width: 1px; height: 2rem; background: #e2e8f0;"></div>
                            <div style="text-align: center; flex: 1;">
                                <p style="font-size: 1.25rem; font-weight: 900; color: #0284c7; margin: 0;">{{ number_format($import->promotions_count ?? 0) }}</p>
                                <p style="font-size: 0.625rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-top: 0.25rem;">ترقيات</p>
                            </div>
                        </div>

                        {{-- Total & Info --}}
                        <div style="text-align: left;">
                            <p style="font-size: 0.75rem; color: #6b7280; font-weight: 500; margin-bottom: 0.25rem;">إجمالي السجلات المعالجة</p>
                            <p style="font-size: 1.5rem; font-weight: 900; color: #111827; margin: 0;">{{ number_format($import->total_agents ?? 0) }} <span style="font-size: 0.875rem; font-weight: 500; color: #9ca3af;">وكيل</span></p>
                        </div>

                        {{-- Actions --}}
                        <div style="display: flex; gap: 0.75rem; justify-content: flex-start;">
                             <a href="{{ url('/admin/data-imports/' . $import->import_id) }}"
                               style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem; border-radius: 0.75rem; border: 1px solid #e5e7eb; background: #fff; color: #374151; font-size: 0.875rem; font-weight: 700; text-decoration: none; transition: all 0.2s;">
                                <x-heroicon-m-eye style="width: 1rem; height: 1rem;"/>
                                التفاصيل
                            </a>
                        </div>
                    </div>

                    @if($import->error_message)
                        <div style="margin-top: 1.5rem; padding: 1rem; border-radius: 0.75rem; background: #fff1f2; border: 1px solid #fecaca; color: #be123c; font-size: 0.875rem; display: flex; align-items: center; gap: 0.5rem;">
                            <x-heroicon-m-exclamation-triangle style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;"/>
                            <strong>خطأ:</strong> {{ $import->error_message }}
                        </div>
                    @endif
                @else
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 0; color: #9ca3af; text-align: center;">
                        <div style="background: #f9fafb; padding: 1.5rem; border-radius: 50%; margin-bottom: 1rem;">
                            <x-heroicon-o-document-plus style="width: 3rem; height: 3rem;"/>
                        </div>
                        <p style="font-size: 1rem; font-weight: 700; color: #374151; margin-bottom: 0.5rem;">لا توجد سجلات استيراد حالية</p>
                        <p style="font-size: 0.875rem; margin-bottom: 1.5rem;">ابدأ برفع أول ملف إكسل لتحديث بيانات الوكلاء.</p>
                        <a href="{{ url('/admin/data-imports/create') }}"
                           style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 2rem; border-radius: 0.75rem; background: #111827; color: #fff; font-size: 0.875rem; font-weight: 700; text-decoration: none;">
                            <x-heroicon-m-plus style="width: 1rem; height: 1rem;"/>
                            رفع أول ملف
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
