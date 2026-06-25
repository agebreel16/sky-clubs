<div>
    {{-- Rewards Hero --}}
    <div class="rewards-hero">
        <div class="rewards-hero-eyebrow">مجموع مكافآتك</div>
        <div class="rewards-hero-amounts">
            <div>
                <div class="rew-amt-big" x-data="countUp({{ $total }})" x-init="init()">
                    <span x-text="formatted"></span><small>₪</small>
                </div>
                <div class="rew-amt-label">الإجمالي</div>
            </div>
            <div>
                <div class="rew-amt-big" style="color:var(--success);" x-data="countUp({{ $paid }})" x-init="init()">
                    <span x-text="formatted"></span><small>₪</small>
                </div>
                <div class="rew-amt-label">المدفوع</div>
            </div>
            <div>
                <div class="rew-amt-big" style="color:var(--warning);" x-data="countUp({{ $total - $paid }})" x-init="init()">
                    <span x-text="formatted"></span><small>₪</small>
                </div>
                <div class="rew-amt-label">المعلّق</div>
            </div>
        </div>
        @if($total > 0)
        <div class="rewards-progress">
            <div class="progress-track">
                <div class="progress-fill" style="width:0%;transition:width 1s;" data-fill-width="{{ round($paid / $total * 100) }}%"></div>
            </div>
            <div class="rewards-progress-meta">
                <span>{{ round($paid / $total * 100) }}% مُحصَّل</span>
                <span>متوقع الصرف خلال 7 أيام</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Rewards List --}}
    <div class="card card-pad" style="margin-top:18px;">
        <div class="section-head" style="margin:0 0 4px;">
            <div>
                <h2>قائمة المكافآت</h2>
                <div class="card-subtitle">{{ $rewards->count() }} مكافآت · مرتّبة من الأحدث</div>
            </div>
        </div>
        @forelse($rewards as $reward)
            @php
                $statusMap = [
                    'paid'    => ['label' => 'مدفوعة', 'svg' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>', 'class' => 'paid'],
                    'pending' => ['label' => 'معلّقة',  'svg' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>', 'class' => 'pending'],
                    'failed'  => ['label' => 'فشلت',   'svg' => '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>', 'class' => 'failed'],
                ];
                $st = $statusMap[$reward->payment_status] ?? $statusMap['pending'];
            @endphp
            <div class="reward-row">
                <div class="reward-icon-circle">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                </div>
                <div class="reward-info">
                    <div class="reward-amount">
                        <span x-data="countUp({{ $reward->amount }})" x-init="init()" x-text="formatted"></span><small>₪</small>
                    </div>
                    <div class="reward-meta">{{ $reward->club?->club_name ?? '—' }} · {{ $reward->created_at?->format('Y-m-d') }}</div>
                    <div class="reward-tags">
                        @if($reward->is_first_arrival)
                            <span class="tag gold">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                أول وصول
                            </span>
                        @endif
                    </div>
                    <div class="reward-timeline">
                        <div class="rt-step done"><div class="rt-dot"></div><span>استحقاق</span></div>
                        <div class="rt-line {{ $reward->payment_status === 'paid' ? 'done' : '' }}"></div>
                        <div class="rt-step {{ $reward->payment_status === 'paid' ? 'done' : ($reward->payment_status === 'pending' ? 'active' : '') }}">
                            <div class="rt-dot"></div>
                            <span>{{ $reward->payment_status === 'failed' ? 'فشل' : 'مدفوعة' }}</span>
                        </div>
                    </div>
                </div>
                <div>
                    <span class="status {{ $st['class'] }}">
                        <span>{!! $st['svg'] !!}</span>{{ $st['label'] }}
                    </span>
                </div>
            </div>
        @empty
            <div class="empty">
                <div style="margin-bottom:10px;color:var(--slate-300);">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/></svg>
                </div>
                لا توجد مكافآت بعد
            </div>
        @endforelse
    </div>
</div>
