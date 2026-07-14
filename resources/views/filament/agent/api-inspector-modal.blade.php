<div style="padding: 8px 0;" x-data="{ copied: null }">

    @if(! $configured)
        <p style="color: #ef4444; font-size: 14px; margin: 0;">
            إعدادات API خطوط الوكلاء غير مكتملة (رابط أو اسم مستخدم مفقود). أكمل الإعداد من صفحة "إعدادات API خطوط الوكلاء" أولاً.
        </p>
    @else
        <p style="margin: 0 0 16px; color: #6b7280; font-size: 13px; font-family: monospace; direction: ltr; text-align: left;">
            agent_id: {{ $context['agentId'] }} &nbsp;|&nbsp; from: {{ $context['from'] }} &nbsp;|&nbsp; to: {{ $context['to'] }}
        </p>

        @foreach($results as $apiName => $result)
            <div style="margin-bottom: 20px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">

                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-weight: 700; font-size: 14px; font-family: monospace;">{{ $apiName }}</span>

                        @if($result['ok'])
                            <span style="font-size: 12px; padding: 2px 8px; border-radius: 999px; background: #dcfce7; color: #15803d;">
                                HTTP {{ $result['status'] }}
                            </span>
                            @if(($result['isJson'] ?? false) === false)
                                <span style="font-size: 12px; padding: 2px 8px; border-radius: 999px; background: #fef3c7; color: #92400e;">
                                    الرد ليس JSON صالح
                                </span>
                            @endif
                        @else
                            <span style="font-size: 12px; padding: 2px 8px; border-radius: 999px; background: #fee2e2; color: #b91c1c;">
                                فشل الاتصال
                            </span>
                        @endif
                    </div>

                    @if($result['ok'] && $result['raw'])
                        <button
                            type="button"
                            @click="
                                navigator.clipboard.writeText(@js($result['raw'])).then(() => {
                                    copied = '{{ $apiName }}';
                                    setTimeout(() => copied = null, 2000);
                                });
                            "
                            style="padding: 4px 10px; background: #0ea5e9; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; font-family: inherit;"
                            :style="copied === '{{ $apiName }}' ? 'background: #10b981;' : ''"
                        >
                            <span x-show="copied !== '{{ $apiName }}'">نسخ</span>
                            <span x-show="copied === '{{ $apiName }}'">✓ تم النسخ</span>
                        </button>
                    @endif
                </div>

                <div style="padding: 12px 14px;">
                    @if($result['ok'])
                        <pre dir="ltr" style="margin: 0; max-height: 320px; overflow: auto; font-size: 12px; font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 12px; border-radius: 6px; white-space: pre-wrap; word-break: break-all;">{{ $result['raw'] }}</pre>
                    @else
                        <p style="color: #b91c1c; font-size: 13px; margin: 0;">{{ $result['error'] }}</p>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
