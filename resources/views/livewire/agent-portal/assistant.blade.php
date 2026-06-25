<div style="height:100%;display:flex;flex-direction:column;"
     x-data="{
         scrollToBottom() {
             $nextTick(() => {
                 const el = document.getElementById('chat-messages');
                 if (el) el.scrollTop = el.scrollHeight;
             });
         }
     }"
     x-init="scrollToBottom()"
     x-on:livewire-request-complete.window="scrollToBottom()">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 18px 14px;border-bottom:1px solid var(--border);flex-shrink:0;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:34px;height:34px;border-radius:50%;background:var(--primary);display:grid;place-items:center;flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="17" height="17">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:7px;">
                    <span style="font-size:14px;font-weight:700;color:var(--text);">المساعد الذكي</span>
                    <span style="font-size:10px;font-weight:600;color:var(--primary);background:color-mix(in srgb,var(--primary) 12%,transparent);border:1px solid color-mix(in srgb,var(--primary) 25%,transparent);border-radius:20px;padding:1px 7px;letter-spacing:.03em;">تجريبي</span>
                </div>
            </div>
        </div>
        @if(count($messages) > 0)
            <button wire:click="clearChat"
                    style="font-size:11px;color:var(--text-muted,#94a3b8);background:none;border:1px solid var(--border);border-radius:7px;padding:5px 10px;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.color='var(--danger)';this.style.borderColor='var(--danger)'"
                    onmouseout="this.style.color='var(--text-muted,#94a3b8)';this.style.borderColor='var(--border)'">
                مسح
            </button>
        @endif
    </div>

    {{-- Messages --}}
    <div id="chat-messages" style="flex:1;overflow-y:auto;padding:16px 18px;display:flex;flex-direction:column;gap:14px;">

        @if(count($messages) === 0)
            <div style="margin:auto;text-align:center;padding:24px 16px;">
                <div style="width:52px;height:52px;border-radius:50%;background:color-mix(in srgb,var(--primary) 12%,transparent);display:grid;place-items:center;margin:0 auto 12px;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="24" height="24">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                </div>
                <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:6px;">مرحباً {{ $agent->agent_name }} 👋</div>
                <div style="font-size:12px;color:var(--text-muted,#94a3b8);line-height:1.7;">
                    اسألني عن وضعك في الحملة أو ماذا تحتاج للترقية.
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:14px;">
                    @foreach(['شو ناقصني عشان أترقى؟', 'كيف ترتيبي؟', 'كيف أحسّن أدائي؟'] as $hint)
                        <button wire:click="$set('userMessage', '{{ $hint }}')"
                                style="font-size:11px;border:1px solid var(--border);border-radius:20px;padding:5px 12px;background:var(--surface);color:var(--text);cursor:pointer;transition:all .15s;"
                                onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
                                onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--text)'">
                            {{ $hint }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @foreach($messages as $msg)
            @if($msg['role'] === 'user')
                <div style="display:flex;justify-content:flex-end;">
                    <div style="max-width:80%;background:var(--primary);color:white;border-radius:16px 16px 4px 16px;padding:10px 14px;font-size:13px;line-height:1.6;word-break:break-word;">
                        {{ $msg['content'] }}
                    </div>
                </div>
            @else
                <div style="display:flex;justify-content:flex-start;gap:8px;align-items:flex-end;">
                    <div style="width:26px;height:26px;border-radius:50%;background:var(--primary);display:grid;place-items:center;flex-shrink:0;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div style="max-width:80%;background:var(--surface);border:1px solid var(--border);color:var(--text);border-radius:16px 16px 16px 4px;padding:10px 14px;font-size:13px;line-height:1.7;word-break:break-word;">
                        {!! nl2br(e($msg['content'])) !!}
                    </div>
                </div>
            @endif
        @endforeach

        {{-- Loading --}}
        <div wire:loading.flex wire:target="sendMessage" style="justify-content:flex-start;gap:8px;align-items:flex-end;">
            <div style="width:26px;height:26px;border-radius:50%;background:var(--primary);display:grid;place-items:center;flex-shrink:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </div>
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px 16px 16px 4px;padding:12px 16px;">
                <div style="display:flex;gap:4px;align-items:center;">
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--primary);animation:dot-bounce .9s infinite;animation-delay:0s;display:inline-block;"></span>
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--primary);animation:dot-bounce .9s infinite;animation-delay:.2s;display:inline-block;"></span>
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--primary);animation:dot-bounce .9s infinite;animation-delay:.4s;display:inline-block;"></span>
                </div>
            </div>
        </div>

        {{-- Error --}}
        @if($error)
            <div style="background:color-mix(in srgb,var(--danger,#ef4444) 10%,transparent);border:1px solid color-mix(in srgb,var(--danger,#ef4444) 30%,transparent);border-radius:10px;padding:10px 14px;font-size:12px;color:var(--danger,#ef4444);text-align:center;">
                ⚠ {{ $error }}
            </div>
        @endif

    </div>

    {{-- Input --}}
    <div style="padding:12px 16px 14px;border-top:1px solid var(--border);flex-shrink:0;"
         x-data="{
             submit(e) {
                 if (e.key === 'Enter' && !e.shiftKey) {
                     e.preventDefault();
                     $wire.sendMessage();
                 }
             }
         }">
        <form wire:submit.prevent="sendMessage" style="display:flex;gap:8px;align-items:flex-end;">
            <textarea
                wire:model="userMessage"
                @keydown="submit($event)"
                placeholder="اكتب سؤالك... (Enter للإرسال)"
                rows="1"
                style="flex:1;resize:none;border:1px solid var(--border);border-radius:12px;padding:10px 14px;font-size:13px;font-family:inherit;color:var(--text);background:var(--surface);outline:none;transition:border-color .15s;line-height:1.5;"
                onfocus="this.style.borderColor='var(--primary)'"
                onblur="this.style.borderColor='var(--border)'"
                oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"
                wire:loading.attr="disabled"
                wire:target="sendMessage"></textarea>
            <button type="submit"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    wire:target="sendMessage"
                    style="width:40px;height:40px;border-radius:50%;background:var(--primary);border:none;cursor:pointer;display:grid;place-items:center;flex-shrink:0;transition:opacity .15s,transform .1s;"
                    onmouseover="this.style.transform='scale(1.05)'"
                    onmouseout="this.style.transform='scale(1)'">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
            </button>
        </form>
    </div>

</div>

<style>
@keyframes dot-bounce {
    0%, 80%, 100% { transform: translateY(0); opacity:.4; }
    40%            { transform: translateY(-6px); opacity:1; }
}
</style>
