<div>
    <div class="section-head">
        <div>
            <h2 style="font-size:22px;display:flex;align-items:center;gap:8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                الإشعارات
            </h2>
            <div class="card-subtitle">{{ $notifications->total() }} إشعار إجمالاً</div>
        </div>
    </div>

    <div class="notif-filters">
        <button wire:click="$set('filter','all')" class="chip {{ $filter === 'all' ? 'active' : '' }}">الكل</button>
        <button wire:click="$set('filter','unread')" class="chip {{ $filter === 'unread' ? 'active' : '' }}">
            غير مقروءة
            @php $unreadCount = $agent->agentNotifications()->where('is_read', false)->count(); @endphp
            @if($unreadCount > 0)<span class="count">{{ $unreadCount }}</span>@endif
        </button>
        <button wire:click="$set('filter','promotion')" class="chip {{ $filter === 'promotion' ? 'active' : '' }}">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
            ترقية
        </button>
        <button wire:click="$set('filter','demotion')" class="chip {{ $filter === 'demotion' ? 'active' : '' }}">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            تهبيط
        </button>
        <button wire:click="$set('filter','warning')" class="chip {{ $filter === 'warning' ? 'active' : '' }}">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>
            تحذير
        </button>
        <button wire:click="markAllRead" class="mark-all">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            علّم الكل مقروءاً
        </button>
    </div>

    @forelse($notifications as $notif)
        @php
            $svgIcons = [
                'promotion'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>',
                'demotion'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>',
                'warning'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>',
                'achievement' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            ];
            $defaultSvg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>';
            $typeClass = ['promotion' => 'promo', 'demotion' => 'demo', 'warning' => 'warn', 'achievement' => 'ach'];
            $icon = $svgIcons[$notif->notification_type] ?? $defaultSvg;
            $tc = $typeClass[$notif->notification_type] ?? 'info';
        @endphp
        <div class="notif t-{{ $tc }}{{ !$notif->is_read ? ' unread' : '' }}"
             wire:click="markRead('{{ $notif->notification_id }}')">
            <div class="notif-icon">{!! $icon !!}</div>
            <div class="notif-body">
                <div class="notif-title">{{ $notif->title }}</div>
                <div class="notif-desc">{{ $notif->body }}</div>
                <div class="notif-foot">
                    <span class="notif-time">{{ $notif->sent_at?->diffForHumans() }}</span>
                    <span class="notif-read">
                        @if($notif->is_read)
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> مقروء
                        @else
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/></svg> غير مقروء
                        @endif
                    </span>
                </div>
            </div>
        </div>
    @empty
        <div class="empty">لا توجد إشعارات في هذا الفلتر</div>
    @endforelse

    <div style="margin-top:16px;">{{ $notifications->links() }}</div>
</div>
