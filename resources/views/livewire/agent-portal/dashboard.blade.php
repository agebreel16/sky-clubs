<div>
    <style>
        @media (prefers-reduced-motion: reduce) {
            .js-section-in, .js-countup { animation: none !important; }
        }

        /* دخول متتابع للأقسام عند التحميل */
        @keyframes sectionIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .js-section-in { animation: sectionIn .5s ease-out both; }

        /* ارتفاع hover لكروت خطوط الحملة والكرت العلوي (ديسكتوب فقط) */
        @media (hover: hover) and (pointer: fine) {
            .eq-line { transition: transform .2s ease, box-shadow .2s ease; }
            .eq-line:hover { transform: translateY(-2px); box-shadow: var(--shadow-sm); }
            .hero-stat-tile { transition: transform .2s ease, box-shadow .2s ease; }
            .hero-stat-tile:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,.25); }
        }

        /* دوران بصري لأيقونة إعادة المزامنة عند الضغط */
        .sync-icon-btn { cursor: pointer; border-radius: 50%; transition: background .2s ease; display: inline-flex; }
        .sync-icon-btn:hover { background: rgba(255,255,255,.12); }
        .sync-icon-btn.spin svg { animation: syncSpin .6s ease-out; }
        @keyframes syncSpin { to { transform: rotate(360deg); } }

        /* pop-in لشارات الإنجاز */
        @keyframes badgePopIn { 0% { transform: scale(.8); opacity: 0; } 70% { transform: scale(1.06); opacity: 1; } 100% { transform: scale(1); } }
        .score-badge--done { animation: badgePopIn .45s cubic-bezier(.34,1.56,.64,1) both; }
        .journey-station--done .journey-dot svg { animation: badgePopIn .45s cubic-bezier(.34,1.56,.64,1) both; }

        .hero-grid { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 16px; align-items: stretch; }
        .eq-card { display: flex; flex-direction: column; }
        .eq-stack { display: flex; flex-direction: column; gap: 10px; flex: 1; justify-content: space-between; }
        .eq-line { text-align: center; padding: 12px 10px; border-radius: 12px; background: var(--slate-50); }
        .eq-line-value { font-size: 24px; font-weight: 800; line-height: 1.1; font-variant-numeric: tabular-nums; color: var(--slate-800); }
        .eq-line-label { font-size: 11px; font-weight: 600; color: var(--slate-500); margin-top: 4px; }
        .eq-line-total { background: rgba(16,185,129,.08); border: 1px solid rgba(16,185,129,.25); }
        .eq-line-total .eq-line-value { color: var(--success); font-size: 28px; }
        .eq-line-total .eq-line-label { color: var(--success); font-weight: 700; }
        .eq-line-loss { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25); }
        .eq-line-loss .eq-line-value { color: #ef4444; font-size: 28px; }
        .eq-line-loss .eq-line-label { color: #ef4444; font-weight: 700; }
        @media (max-width: 900px) {
            .hero-grid { grid-template-columns: 1fr; }
            .eq-stack { flex-direction: row; }
            .eq-line { flex: 1; }
        }

        .score-card {
            position: relative;
            border-radius: 20px;
            padding: 24px;
            background: var(--card);
            border: 1px solid rgba(15, 23, 42, .04);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            transition: transform .25s ease, box-shadow .25s ease;
        }
        .score-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-hover); }
        .score-card::before { content: ''; position: absolute; inset: 0 0 auto 0; height: 4px; }
        .score-card--priority { background: linear-gradient(160deg, rgba(14,165,233,.06) 0%, var(--card) 60%); }

        .score-card--success::before { background: var(--success); }
        .score-card--active::before  { background: var(--primary); }
        .score-card--warning::before { background: var(--warning); }

        .score-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
        .score-eyebrow { font-size: 11px; letter-spacing: .08em; color: var(--slate-500); text-transform: uppercase; font-weight: 700; }
        .score-club { font-size: 19px; font-weight: 800; color: var(--slate-900); margin-top: 4px; line-height: 1.25; }

        .score-ring { width: 96px; height: 96px; border-radius: 50%; display: grid; place-items: center; flex-shrink: 0; }
        .score-ring-inner { width: 76px; height: 76px; border-radius: 50%; background: #fff; display: flex; flex-direction: column; align-items: center; justify-content: center; box-shadow: inset 0 0 0 1px rgba(15,23,42,.05); }
        .score-ring-num { font-size: 26px; font-weight: 800; line-height: 1; font-variant-numeric: tabular-nums; }
        .score-ring-den { font-size: 11px; color: var(--slate-400); font-weight: 700; margin-top: 2px; }

        .score-rows { display: flex; flex-direction: column; gap: 12px; }
        .score-row { padding: 12px 16px; border-radius: 12px; background: var(--slate-50); }
        .score-row-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .score-row-label { font-size: 12.5px; font-weight: 600; color: var(--slate-500); }
        .score-row-val { font-size: 14px; color: var(--slate-700); margin-top: 3px; font-weight: 600; font-variant-numeric: tabular-nums; }
        .score-row-bar { height: 6px; background: var(--slate-200); border-radius: 999px; margin-top: 8px; overflow: hidden; }
        .score-row-fill { height: 100%; border-radius: 999px; width: 0%; transition: width 1.1s cubic-bezier(.22,.85,.32,1); }

        .score-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .score-badge--done { color: var(--success); background: rgba(16,185,129,.12); }
        .score-badge--warn { color: var(--warning); background: rgba(245,158,11,.14); }

        .score-foot { margin-top: 16px; padding: 12px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; text-align: center; }
        .score-foot--done { color: var(--success); background: rgba(16,185,129,.10); }
        .score-foot--warn { color: var(--warning); background: rgba(245,158,11,.12); }

        .journey-card { overflow: visible; }
        .journey-track-wrap { position: relative; padding-top: 8px; }
        .journey-track { position: relative; height: 10px; background: #e5e7eb; border-radius: 999px; overflow: visible; }
        .journey-fill {
            position: absolute; inset: 0; width: 0%;
            background: linear-gradient(90deg, #22c55e 0%, #3b82f6 100%);
            border-radius: 999px;
            transition: width 1.5s cubic-bezier(.2,.8,.2,1);
            box-shadow: 0 2px 10px rgba(34,197,94,.35);
        }
        .journey-fill::after {
            content: ''; position: absolute; top: 0; right: 0; bottom: 0; width: 22px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.55), transparent);
            animation: journeyShine 2.2s infinite; border-radius: 999px;
        }
        @keyframes journeyShine { 0% { transform: translateX(24px); } 100% { transform: translateX(-140px); } }

        .journey-stations { display: flex; align-items: flex-start; margin-top: 16px; }
        .journey-station {
            position: relative; flex: 1; min-width: 0;
            display: flex; flex-direction: column; align-items: center; gap: 8px;
            opacity: 0; animation: journeyIn .5s ease-out forwards;
        }
        @keyframes journeyIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

        .journey-dot {
            width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center;
            border: none; cursor: pointer; transition: transform .2s ease; font-size: 10px; font-weight: 700;
        }
        .journey-dot:hover { transform: scale(1.08); }
        .journey-station--locked .journey-dot { background: #e5e7eb; color: #9ca3af; }
        .journey-station--target .journey-dot { background: #eff6ff; border: 2px dashed #93c5fd; color: #3b82f6; }
        .journey-station--done .journey-dot { background: #22c55e; color: #fff; box-shadow: 0 0 0 4px rgba(34,197,94,.18); }
        .journey-station--current .journey-dot {
            width: 40px; height: 40px; background: #3b82f6; color: #fff;
            box-shadow: 0 0 0 6px rgba(59,130,246,.22);
            animation: journeyPulse 2s infinite;
        }
        @keyframes journeyPulse {
            0%,100% { box-shadow: 0 0 0 6px rgba(59,130,246,.22); }
            50%      { box-shadow: 0 0 0 11px rgba(59,130,246,.06); }
        }
        .journey-dot-num { opacity: .6; }

        .journey-label { text-align: center; }
        .journey-label-name { font-size: 11.5px; font-weight: 700; color: var(--slate-700); white-space: nowrap; }
        .journey-label-req { font-size: 10px; color: var(--slate-400); margin-top: 1px; white-space: nowrap; }
        .journey-station--done .journey-label-name { color: #15803d; }
        .journey-station--current .journey-label-name { color: #3b82f6; }
        .journey-station--locked .journey-label-name, .journey-station--locked .journey-label-req,
        .journey-station--target .journey-label-name, .journey-station--target .journey-label-req { color: #9ca3af; }

        .journey-marker { position: absolute; bottom: calc(100% + 14px); right: 50%; transform: translateX(50%); display: flex; flex-direction: column; align-items: center; z-index: 3; }
        .journey-marker-tip { font-size: 10.5px; font-weight: 700; color: #fff; background: var(--slate-900); padding: 5px 12px; border-radius: 999px; white-space: nowrap; box-shadow: var(--shadow-sm); }
        .journey-marker-arrow { width: 0; height: 0; border-left: 5px solid transparent; border-right: 5px solid transparent; border-top: 6px solid var(--slate-900); margin-top: -1px; }
        .journey-marker-icon { font-size: 18px; margin-top: 2px; animation: journeyFloat 2.4s ease-in-out infinite; filter: drop-shadow(0 3px 4px rgba(0,0,0,.2)); }
        @keyframes journeyFloat { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-5px); } }

        .journey-pop {
            position: absolute; bottom: calc(100% + 56px); right: 50%; transform: translateX(50%) translateY(6px);
            width: 190px; background: var(--slate-900); color: #fff; border-radius: 12px; padding: 12px 14px;
            box-shadow: var(--shadow-lg); font-size: 12px; opacity: 0; pointer-events: none; z-index: 5;
            transition: opacity .18s ease, transform .18s ease;
        }
        .journey-station:not(.journey-station--current) .journey-pop { bottom: calc(100% + 12px); }
        .journey-pop::after {
            content: ''; position: absolute; top: 100%; right: 50%; transform: translateX(50%);
            border: 6px solid transparent; border-top-color: var(--slate-900);
        }
        .journey-station:hover .journey-pop,
        .journey-station.journey-pop-open .journey-pop { opacity: 1; transform: translateX(50%) translateY(0); pointer-events: auto; }
        .journey-pop-title { font-weight: 800; margin-bottom: 6px; }
        .journey-pop-date { color: #6ee7b7; font-size: 11px; margin-bottom: 6px; }
        .journey-pop-row { display: flex; justify-content: space-between; gap: 10px; padding: 3px 0; color: var(--slate-300); }
        .journey-pop-row b { color: #fff; }

        .journey-encourage { margin-top: 24px; text-align: center; font-size: 13px; font-weight: 700; color: var(--slate-700); }

        .journey-confetti-piece {
            position: fixed; top: -10px; width: 8px; height: 8px; z-index: 9997; pointer-events: none;
            animation: journeyConfettiFall 2.6s ease-in forwards;
        }
        @keyframes journeyConfettiFall {
            to { transform: translateY(100vh) rotate(540deg); opacity: 0; }
        }

        @media (max-width: 768px) {
            .journey-stations { flex-direction: column; gap: 0; margin-top: 8px; }
            .journey-track { display: none; }
            .journey-station {
                flex-direction: row; align-items: flex-start; gap: 14px;
                text-align: right; padding-bottom: 22px; opacity: 1; animation: none;
            }
            .journey-marker { display: none; }
            .journey-station::before {
                content: ''; position: absolute; right: 17px; top: 38px; bottom: 0; width: 2px; background: var(--slate-200);
            }
            .journey-station--done::before { background: #22c55e; }
            .journey-station:last-child::before { display: none; }
            .journey-label { text-align: right; flex: 1; }
            .journey-pop {
                position: static; transform: none; width: auto; margin-top: 0;
                max-height: 0; opacity: 0; pointer-events: none; overflow: hidden;
                transition: max-height .25s ease, opacity .2s ease, margin-top .25s ease;
            }
            .journey-pop::after { display: none; }
            .journey-station.journey-pop-open .journey-pop { max-height: 220px; opacity: 1; pointer-events: auto; margin-top: 8px; }
        }
    </style>

    <div class="hero-grid">

    {{-- Hero Card --}}
    @if($agent->club)
        @php
            $rankInClub = \App\Models\Agent::where('current_club_id', $agent->current_club_id)
                ->whereNotNull('current_club_id')
                ->orderByDesc('transfer_count')
                ->pluck('agent_id')
                ->search($agent->agent_id);
            $rankInClub = ($rankInClub !== false) ? $rankInClub + 1 : '—';
            $daysIn      = $agent->entry_date ? (int) $agent->entry_date->diffInDays(now()) : 0;
            $isTop       = !\App\Models\Club::where('is_active', true)->where('club_order', '>', $agent->club->club_order)->exists();
        @endphp
        <div class="hero js-section-in" style="animation-delay:0ms;">
            <div class="grid-bg"></div>
            <div class="hero-row">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="30" height="30"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                </div>
                <div style="flex:1;">
                    <div class="hero-eyebrow">ناديك الحالي</div>
                    <div class="hero-title">{{ $agent->club->club_name }}</div>
                    <div class="hero-sub">{{ $isTop ? 'المستوى الأعلى' : 'عضو نشط' }}</div>
                </div>
                @if($agent->is_first_arrival)
                    <div class="first-arrival-badge">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="12" height="12"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01z"/></svg>
                        أول وصول
                    </div>
                @endif
            </div>
            <div class="hero-divider"></div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    انضممت منذ {{ $daysIn }} يوماً
                </div>
                <div class="rank-medal">
                    @php
                        $rankStyles = [
                            1 => 'background:#eab308;color:white;',
                            2 => 'background:#94a3b8;color:white;',
                            3 => 'background:#cd7f32;color:white;',
                        ];
                        $rs = $rankStyles[$rankInClub] ?? 'background:var(--slate-200);color:var(--slate-700);';
                    @endphp
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;{{ $rs }}font-size:10px;font-weight:700;">
                        {{ is_numeric($rankInClub) && $rankInClub <= 3 ? $rankInClub : '#' . $rankInClub }}
                    </span>
                    رتبتك #{{ $rankInClub }} في النادي
                </div>
                @if($agent->last_self_sync_at)
                    <div class="hero-meta-item sync-icon-btn" onclick="this.classList.remove('spin'); void this.offsetWidth; this.classList.add('spin');">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                        آخر مزامنة: {{ $agent->last_self_sync_at->diffForHumans() }}
                    </div>
                @endif
                <div class="hero-meta-item live-dot">اتصال حي</div>
            </div>

            {{-- Stats Strip --}}
            @php
                $heroRatioGaugePct = $agent->transfer_percentage > 0
                    ? min((int)round($agent->transfer_percentage / 60 * 100), 100)
                    : 0;
                $heroRatioAchieved = $agent->transfer_percentage >= 60;
            @endphp
            <div style="border-top:1px solid rgba(255,255,255,0.25);margin-top:16px;padding-top:14px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(70px,1fr));gap:8px;">

                    <div class="hero-stat-tile" style="background:rgba(0,0,0,0.18);border:1px solid rgba(255,255,255,0.25);border-radius:12px;padding:14px 10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                        <div class="js-countup" style="font-size:22px;font-weight:800;color:#fff;line-height:1.1;">{{ number_format($agent->new_line_count) }}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.85);margin-top:4px;font-weight:600;">خطوط جديدة</div>
                    </div>

                    <div class="hero-stat-tile" style="background:rgba(0,0,0,0.18);border:1px solid rgba(255,255,255,0.25);border-radius:12px;padding:14px 10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                        <div class="js-countup" style="font-size:22px;font-weight:800;color:#fff;line-height:1.1;">{{ number_format($agent->transfer_count) }}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.85);margin-top:4px;font-weight:600;">خطوط التحويل</div>
                    </div>

                    <div class="hero-stat-tile" style="background:rgba(0,0,0,0.18);border:1px solid rgba(255,255,255,0.25);border-radius:12px;padding:14px 10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                        <div class="js-countup" style="font-size:22px;font-weight:800;color:#fff;line-height:1.1;">{{ number_format($agent->campaign_increase) }}</div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.85);margin-top:4px;font-weight:600;">إجمالي الزيادة</div>
                    </div>

                    <div class="hero-stat-tile" style="background:rgba(0,0,0,0.18);border:1px solid rgba(255,255,255,0.25);border-radius:12px;padding:14px 10px;text-align:center;box-shadow:0 2px 8px rgba(0,0,0,0.2);">
                        <div style="font-size:22px;font-weight:800;color:{{ $heroRatioAchieved ? '#86efac' : '#fda4af' }};line-height:1.1;"><span class="js-countup">{{ round($agent->transfer_percentage) }}</span><span style="font-size:14px;font-weight:700;">%</span></div>
                        <div style="font-size:11px;color:rgba(255,255,255,0.85);margin-top:4px;font-weight:600;">نسبة التحويل</div>
                    </div>

                </div>
            </div>
        </div>
    @else
        @php
            $firstClub = \App\Models\Club::where('is_active', true)->orderBy('club_order')->first();
            $needed    = $firstClub ? max(0, $firstClub->required_increase - $agent->campaign_increase) : 0;
        @endphp
        <div class="hero js-section-in" style="background:linear-gradient(135deg,#475569 0%,#64748b 100%);animation-delay:0ms;">
            <div class="grid-bg"></div>
            <div class="hero-row">
                <div class="hero-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="30" height="30"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.5" fill="currentColor"/></svg>
                </div>
                <div style="flex:1;">
                    <div class="hero-eyebrow">حالتك</div>
                    <div class="hero-title">لم تنضم لأي نادٍ بعد</div>
                    <div class="hero-sub">تحتاج {{ $needed }} زيادة فقط للوصول إلى {{ $firstClub?->club_name ?? 'نادي البداية' }}</div>
                </div>
            </div>
            <div class="hero-divider"></div>
            <div class="hero-meta">
                <div class="hero-meta-item">
                    <strong>ابدأ الآن وانضم إلى أول نادٍ</strong>
                    <span style="display:inline-block;animation:pulse 1.4s infinite;">←</span>
                </div>
                @if($agent->last_self_sync_at)
                    <div class="hero-meta-item sync-icon-btn" onclick="this.classList.remove('spin'); void this.offsetWidth; this.classList.add('spin');">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 12a9 9 0 1 1-6.219-8.56"/><polyline points="21 3 21 9 15 9"/></svg>
                        آخر مزامنة: {{ $agent->last_self_sync_at->diffForHumans() }}
                    </div>
                @endif
                <div class="hero-meta-item live-dot">اتصال حي</div>
            </div>
        </div>
    @endif

    {{-- خطوط الحملة: قبل / اليوم / الفرق --}}
    @php
        $eqBefore = (int) $agent->pre_campaign_count;
        $eqNow    = $agent->true_active_subs !== null
            ? (int) $agent->true_active_subs
            : (int) $agent->current_total;
        $eqDiff   = max(0, $eqNow - $eqBefore);
        $loss     = $agent->true_deficit;
    @endphp
    <div class="card card-pad eq-card js-section-in" style="animation-delay:90ms;">
        <div class="section-head" style="margin:0 0 14px;">
            <h2>خطوط الحملة</h2>
        </div>
        <div class="eq-stack">
            <div class="eq-line">
                <div class="eq-line-value js-countup" style="color:var(--slate-400);">{{ number_format($eqBefore) }}</div>
                <div class="eq-line-label">قبل الحملة</div>
            </div>
            <div class="eq-line">
                <div class="eq-line-value js-countup" style="color:var(--primary);">{{ number_format($eqNow) }}</div>
                <div class="eq-line-label">حتى اليوم</div>
            </div>
            <div class="eq-line {{ $loss > 0 ? 'eq-line-loss' : 'eq-line-total' }}">
                <div class="eq-line-value js-countup">{{ $loss > 0 ? '-'.number_format($loss) : number_format($eqDiff) }}</div>
                <div class="eq-line-label">{{ $loss > 0 ? 'نقص عن البداية' : 'داخل الحملة' }}</div>
            </div>
        </div>
    </div>

    </div>

    {{-- Decline Warning Banner --}}
    @if($loss !== null && $loss > 0)
        <div class="warn" style="border-color:#ef4444;background:rgba(239,68,68,0.08);">
            <div class="warn-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 17 13.5 8.5 8.5 13.5 2 7"/><polyline points="16 17 22 17 22 11"/></svg>
            </div>
            <div class="warn-body">
                <div class="warn-title" style="color:#ef4444;">نقص! خسرت {{ number_format($loss) }} خط عن بداية الحملة</div>
                <div class="warn-desc">عدد خطوطك النشطة حالياً ({{ number_format($eqNow) }}) أقل من عدد بداية الحملة ({{ number_format($eqBefore) }}).</div>
            </div>
        </div>
    @endif

    {{-- Violator Banner --}}
    @if($agent->is_violator)
        <div class="warn" style="border-color:#ef4444;background:rgba(239,68,68,0.08);">
            <div class="warn-icon" style="background:rgba(239,68,68,0.15);color:#ef4444;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"/></svg>
            </div>
            <div class="warn-body">
                <div class="warn-title" style="color:#ef4444;">تنبيه: حسابك مُعلَّق من قِبَل الإدارة</div>
                <div class="warn-desc">للاستفسار تواصل مع الموزع .</div>
            </div>
        </div>
    @endif

    {{-- بطاقة الحالة الحية --}}
    @php
        $clubs    = \App\Models\Club::where('is_active', true)->orderBy('club_order')->get();
        $nextClub = $agent->club
            ? $clubs->where('club_order', '>', $agent->club->club_order)->first()
            : $clubs->first();

        if ($agent->is_violator) {
            $agentState = 'violator';
        } elseif (!$agent->club) {
            $agentState = 'no_club';
        } else {
            $agentState = 'safe';
        }

        if ($agent->club) {
            $scIncrease    = $agent->campaign_increase;
            $scNeeded      = $nextClub ? max(0, $nextClub->required_increase - $scIncrease) : 0;
            $scTransferMet = !$nextClub || $agent->transfer_count >= ($nextClub->required_transfer_count ?? 0);
            $scRatioPct    = $nextClub ? ($nextClub->required_transfer_percentage ?? 0) : 0;
            $scAgentRatio  = $agent->campaign_increase > 0
                ? round($agent->transfer_count / $agent->campaign_increase * 100, 1) : 0;
            $scRatioMet    = $scRatioPct <= 0 || $scAgentRatio >= ($scRatioPct * 100);
            $scTotalMet    = !$nextClub || $scIncrease >= $nextClub->required_increase;

            if (!$nextClub) {
                $safeLabel = 'أنت في أعلى نادٍ — استمر في التميز';
                $safeDot = '#16a34a'; $safeBg = '#f0fdf4'; $safeBorder = '#86efac';
            } elseif ($scTotalMet && $scTransferMet && $scRatioMet) {
                $safeLabel = 'مؤهل للترقية إلى ' . $nextClub->club_name . ' — في انتظار التحديث';
                $safeDot = '#10b981'; $safeBg = '#f0fdf4'; $safeBorder = '#6ee7b7';
            } elseif (!$scTransferMet) {
                $remainT   = ($nextClub->required_transfer_count ?? 0) - $agent->transfer_count;
                $safeLabel = 'ركّز على التحويل — متبقي ' . $remainT . ' خط للترقية';
                $safeDot = '#d97706'; $safeBg = '#fffbeb'; $safeBorder = '#fde68a';
            } elseif (!$scRatioMet) {
                $safeLabel = 'نسبة التحويل دون المطلوب — ' . $scAgentRatio . '% / ' . ($scRatioPct * 100) . '%';
                $safeDot = '#d97706'; $safeBg = '#fffbeb'; $safeBorder = '#fde68a';
            } elseif ($scNeeded <= (int)ceil($nextClub->required_increase * 0.2)) {
                $safeLabel = 'اقتربت! متبقي ' . $scNeeded . ' زيادة لنادي ' . $nextClub->club_name;
                $safeDot = '#0ea5e9'; $safeBg = '#eff6ff'; $safeBorder = '#bae6fd';
            } else {
                $safeLabel = 'وضعك جيد — تحتاج ' . $scNeeded . ' زيادة لنادي ' . $nextClub->club_name;
                $safeDot = '#16a34a'; $safeBg = '#f0fdf4'; $safeBorder = '#86efac';
            }
        } else {
            $safeLabel = ''; $safeDot = '#16a34a'; $safeBg = '#f0fdf4'; $safeBorder = '#86efac';
        }

        $stateMap = [
            'safe'     => ['bg' => $safeBg,   'border' => $safeBorder, 'dot' => $safeDot,   'label' => $safeLabel],
            'violator' => ['bg' => '#fef2f2',  'border' => '#fca5a5',  'dot' => '#dc2626',  'label' => 'الحساب مُعلَّق — تواصل مع مشرفك المباشر'],
            'no_club'  => ['bg' => '#f8fafc',  'border' => '#cbd5e1',  'dot' => '#94a3b8',  'label' => 'لم تنضم لأي نادٍ بعد — ابدأ الآن'],
        ];
        $sc = $stateMap[$agentState];
    @endphp
    <div style="margin-top:16px;padding:14px 18px;background:{{ $sc['bg'] }};border:1.5px solid {{ $sc['border'] }};border-radius:14px;display:flex;align-items:center;gap:12px;">
        <div style="width:10px;height:10px;border-radius:50%;background:{{ $sc['dot'] }};flex-shrink:0;animation:pulse 2s infinite;box-shadow:0 0 0 3px {{ $sc['bg'] }};"></div>
        <div style="font-size:14px;font-weight:600;color:{{ $sc['dot'] }};">{{ $sc['label'] }}</div>
    </div>

    {{-- Grid: الهدف القادم (يسار) + متطلبات+ضغط (يمين) --}}
    @php
        // $clubs و $nextClub محسوبتان مسبقاً في block الحالة الحية أعلاه
    @endphp
    {{-- بطاقة تحفيزية للوكلاء خارج الأندية --}}
    @if(!$agent->club && $nextClub)
    @php
        $motReqTransfer = (int)($nextClub->required_transfer_count ?? 0);
        $motReqNewLines = max(0, (int)($nextClub->required_increase ?? 0) - $motReqTransfer);
        $motGotTransfer = (int)$agent->transfer_count;
        $motGotNewLines = (int)$agent->new_line_count;

        $motTPct = $motReqTransfer > 0 ? min(100, (int)round($motGotTransfer / $motReqTransfer * 100)) : 100;
        $motNPct = $motReqNewLines > 0 ? min(100, (int)round($motGotNewLines / $motReqNewLines * 100)) : 100;

        $motWeakest       = min($motTPct, $motNPct);
        $motFocusTransfer = $motTPct <= $motNPct;
        $motRemainT       = max(0, $motReqTransfer - $motGotTransfer);
        $motRemainN       = max(0, $motReqNewLines - $motGotNewLines);
        $motFocusText     = $motFocusTransfer
            ? "تحتاج {$motRemainT} خط تحويل إضافي"
            : "تحتاج {$motRemainN} خط جديد إضافي";

        [$motStage, $motHeadline, $motAccent] = match(true) {
            $motWeakest === 0  => ['بداية',  'ابدأ رحلتك نحو نادي '.$nextClub->club_name.'!', '#94a3b8'],
            $motWeakest < 30   => ['تقدم',   'رحلتك بدأت — استمر ولا تتوقف!',                 '#0ea5e9'],
            $motWeakest < 60   => ['منتصف',  'في منتصف الطريق — لا تتوقف الآن!',              '#8b5cf6'],
            $motWeakest < 90   => ['اقتراب', 'اقتربت جداً — خطوات قليلة للنادي!',             '#f59e0b'],
            $motWeakest < 100  => ['أبواب',  'على أبواب النادي — الآن أو لا!',                '#10b981'],
            default            => ['مكتمل',  'أتممت جميع الشروط — في انتظار التحديث!',        '#10b981'],
        };
    @endphp
    <div style="margin-top:18px;padding:14px 18px;border-radius:14px;
                background:var(--sc-surface,#fff);
                border:1px solid var(--sc-border,rgba(0,0,0,.09));
                border-top:3px solid {{ $motAccent }};
                box-shadow:var(--sc-shadow,0 2px 12px rgba(0,0,0,.08));
                display:flex;align-items:center;gap:16px;">
        <span style="flex-shrink:0;font-size:11px;font-weight:700;color:{{ $motAccent }};
                     background:{{ $motAccent }}18;padding:3px 10px;border-radius:999px;
                     border:1px solid {{ $motAccent }}40;white-space:nowrap;">
            {{ $motStage }}
        </span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:var(--sc-text,#1e293b);">
                {{ $motHeadline }}
            </div>
            <div style="font-size:12px;color:var(--sc-text2,#64748b);margin-top:2px;">
                {{ $motFocusText }}
            </div>
        </div>
        <div style="flex-shrink:0;text-align:center;">
            <div style="font-size:12px;font-weight:700;color:{{ $motTPct >= 100 ? '#10b981' : $motAccent }};">
                {{ $motTPct >= 100 ? '✓' : $motTPct.'%' }}
            </div>
            <div style="width:48px;height:4px;background:var(--sc-border,#e2e8f0);border-radius:999px;margin:3px 0;overflow:hidden;">
                <div style="width:{{ $motTPct }}%;height:100%;border-radius:999px;
                            background:{{ $motTPct >= 100 ? '#10b981' : $motAccent }};
                            transition:width 1.2s ease-out;"
                     data-fill-width="{{ $motTPct }}%"></div>
            </div>
            <div style="font-size:10px;color:var(--sc-text3,#94a3b8);">تحويل</div>
        </div>
        @if($motReqNewLines > 0)
        <div style="flex-shrink:0;text-align:center;">
            <div style="font-size:12px;font-weight:700;color:{{ $motNPct >= 100 ? '#10b981' : $motAccent }};">
                {{ $motNPct >= 100 ? '✓' : $motNPct.'%' }}
            </div>
            <div style="width:48px;height:4px;background:var(--sc-border,#e2e8f0);border-radius:999px;margin:3px 0;overflow:hidden;">
                <div style="width:{{ $motNPct }}%;height:100%;border-radius:999px;
                            background:{{ $motNPct >= 100 ? '#10b981' : $motAccent }};
                            transition:width 1.2s ease-out;"
                     data-fill-width="{{ $motNPct }}%"></div>
            </div>
            <div style="font-size:10px;color:var(--sc-text3,#94a3b8);">جديدة</div>
        </div>
        @endif
    </div>
    @endif

    {{-- رحلتك في الأندية --}}
    @php
        $journeyClubs  = $clubs->take(5);
        $agentIncrease = $agent->campaign_increase;
        $stationCount  = $journeyClubs->count();

        $currentIndex = $agent->club
            ? $journeyClubs->search(fn ($c) => $c->club_id === $agent->current_club_id)
            : -1;
        $currentIndex = $currentIndex === false ? -1 : $currentIndex;

        $segment  = $stationCount > 1 ? 100 / ($stationCount - 1) : 100;
        $baseFill = max(0, $currentIndex) * $segment;

        if ($nextClub) {
            $currentReq = $agent->club ? $agent->club->required_increase : 0;
            $span       = max(1, $nextClub->required_increase - $currentReq);
            $frac       = min(1, max(0, ($agentIncrease - $currentReq) / $span));
            $fillPct    = min(100, round($baseFill + $frac * $segment));
        } else {
            $fillPct = 100;
        }

        $passedClubIds = $journeyClubs
            ->filter(fn ($c) => $agent->club && $agent->club->club_order > $c->club_order)
            ->pluck('club_id');

        $achievedAt = \App\Models\HistoryLog::where('agent_id', $agent->agent_id)
            ->where('event_type', 'promotion')
            ->whereIn('to_club_id', $passedClubIds)
            ->orderByDesc('event_timestamp')
            ->get()
            ->unique('to_club_id')
            ->pluck('event_timestamp', 'to_club_id');

        $encourage = match(true) {
            $fillPct >= 100 => '🎉 وصلت لنادي القمة! أنت أسطورة الحملة',
            $fillPct >= 70  => 'قربت توصل! باقي القليل 💪',
            $fillPct >= 40  => 'تقدم ممتاز، كمل بنفس الوتيرة 🚀',
            $fillPct > 0    => 'بداية قوية! أول خطوة بالرحلة الطويلة 🌱',
            default          => 'ابدأ رحلتك الآن نحو نادي الانطلاق',
        };

        if ($nextClub) {
            $remaining = max(0, $nextClub->required_increase - $agentIncrease);
            $markerTip = $remaining > 0
                ? 'متبقي ' . number_format($remaining) . ' خط لِ' . $nextClub->club_name
                : 'جاهز للترقية لِ' . $nextClub->club_name . '!';
        } else {
            $markerTip = '🏆 أنت في القمة!';
        }

        $celebrateKey = 'sc_celebrated_' . $agent->agent_id . '_' . ($agent->current_club_id ?? 'none');
    @endphp
    <div class="card card-pad journey-card js-section-in" style="margin-top:18px;animation-delay:180ms;">
        <div class="score-row-label" style="margin-bottom:18px;">رحلتك في الأندية</div>

        <div class="journey-track-wrap">
            <div class="journey-track">
                <div class="journey-fill" data-fill-width="{{ $fillPct }}%"></div>
            </div>

            <div class="journey-stations">
                @foreach($journeyClubs as $i => $c)
                    @php
                        $passed  = $agent->club && $agent->club->club_order > $c->club_order;
                        $current = $agent->club && $agent->current_club_id === $c->club_id;
                        $target  = !$current && $nextClub && $nextClub->club_id === $c->club_id;
                        $state   = $current ? 'current' : ($passed ? 'done' : ($target ? 'target' : 'locked'));
                        $achDate = $achievedAt->get($c->club_id);
                    @endphp
                    <div class="journey-station journey-station--{{ $state }}" style="animation-delay:{{ $i * 120 }}ms;">
                        @if($state === 'current')
                            <div class="journey-marker" data-celebrate-key="{{ $celebrateKey }}">
                                <div class="journey-marker-tip">{{ $markerTip }}</div>
                                <div class="journey-marker-arrow"></div>
                                <div class="journey-marker-icon">🚀</div>
                            </div>
                        @endif
                        <button type="button" class="journey-dot" data-journey-toggle>
                            @if($state === 'done')
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                            @elseif($state !== 'current')
                                <span class="journey-dot-num">{{ number_format($c->required_increase) }}</span>
                            @endif
                        </button>
                        <div class="journey-label">
                            <div class="journey-label-name">{{ $c->club_name }}</div>
                            <div class="journey-label-req">{{ number_format($c->required_increase) }} خط</div>
                        </div>

                        <div class="journey-pop">
                            <div class="journey-pop-title">{{ $c->club_name }}</div>
                            @if($state === 'done' && $achDate)
                                <div class="journey-pop-date">✓ أنجزته في {{ $achDate->format('d/m/Y') }}</div>
                            @endif
                            <div class="journey-pop-row"><span>الخطوط المطلوبة</span><b>{{ number_format($c->required_increase) }}</b></div>
                            @if($c->required_transfer_count > 0)
                                <div class="journey-pop-row"><span>خطوط التحويل</span><b>{{ number_format($c->required_transfer_count) }}</b></div>
                            @endif
                            @if($c->base_reward_amount > 0)
                                <div class="journey-pop-row"><span>مكافأة الانضمام</span><b>{{ number_format($c->base_reward_amount) }}</b></div>
                            @endif
                            @if($c->first_arrival_reward_amount > 0)
                                <div class="journey-pop-row"><span>مكافأة أول وصول</span><b>{{ number_format($c->first_arrival_reward_amount) }}</b></div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="journey-encourage">{{ $encourage }}</div>
    </div>

    {{-- شروط الأندية: المحافظة على النادي الحالي + الهدف القادم --}}
    @php
        $scoreFor = function ($targetClub) use ($agent) {
            $reqInc    = (int) $targetClub->required_increase;
            $reqTrans  = (int) ($targetClub->required_transfer_count ?? 0);
            $incVal    = (int) $agent->campaign_increase;
            $transVal  = (int) $agent->transfer_count;
            $showTrans = $reqTrans > 0;

            $totalAchieved = min($incVal, $reqInc);
            $totalRequired = $reqInc;
            $pct = $reqInc > 0 ? min(100, (int) round($totalAchieved / $reqInc * 100)) : 100;

            return [
                'club'          => $targetClub,
                'incVal'        => $incVal,
                'reqInc'        => $reqInc,
                'incMet'        => $incVal >= $reqInc,
                'transVal'      => $transVal,
                'reqTrans'      => $reqTrans,
                'transMet'      => $transVal >= $reqTrans,
                'showTrans'     => $showTrans,
                'totalAchieved' => $totalAchieved,
                'totalRequired' => $totalRequired,
                'pct'           => $pct,
            ];
        };

        $maintainScore = $agent->club ? $scoreFor($agent->club) : null;
        $nextScore     = $nextClub ? $scoreFor($nextClub) : null;
    @endphp
    <div class="block-grid cols-2 js-section-in" style="margin-top:18px;align-items:start;animation-delay:270ms;">

        {{-- بطاقة: المحافظة على النادي الحالي (فقط لمن هو منضم لنادٍ) --}}
        @if($maintainScore)
            <div>
                <div class="section-head">
                    <h2> النادي الحالي</h2>
           
                </div>
                @include('livewire.agent-portal.partials.score-card', [
                    'score'     => $maintainScore,
                    'eyebrow'   => 'ناديك الحالي',
                    'metText'   => '✅ محافظ على النادي — مستوفي لجميع الشروط',
                    'unmetText' => '⚠️ انتبه: أنت حالياً دون متطلبات هذا النادي',
                    'variant'   => 'maintain',
                ])
            </div>
        @endif

        {{-- بطاقة: الهدف القادم --}}
        <div @if(!$maintainScore) style="grid-column:1 / -1;" @endif>
            <div class="section-head">
                <h2>الهدف القادم</h2>
            </div>
            @if($agent->club && !$nextClub)
                <div class="next-goal" style="background:linear-gradient(135deg,#fffbeb 0%,#fef3c7 100%);border:1px solid #fde68a;">
                    <div class="next-goal-head">
                        <div class="next-goal-target">
                            <div class="next-goal-target-icon" style="background:var(--grad-gold);">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M6 9H4a2 2 0 0 1-2-2V5h4"/><path d="M18 9h2a2 2 0 0 0 2-2V5h-4"/><path d="M6 22h12"/><path d="M12 17v5"/><path d="M6 2h12v8a6 6 0 0 1-12 0z"/></svg>
                            </div>
                            <div>
                                <div class="next-goal-eyebrow">الحالة</div>
                                <div class="next-goal-name">أنت في القمة</div>
                            </div>
                        </div>
                        <div class="next-goal-need">
                            <div class="next-goal-need-num">100%</div>
                            <div class="next-goal-need-label">أعلى نادٍ في الحملة</div>
                        </div>
                    </div>
                    <div class="progress-track"><div class="progress-fill" style="width:0%;transition:width 1s ease-out;" data-fill-width="100%"></div></div>
                    <div class="progress-need">استمر في التميز للحفاظ على مكانتك</div>
                </div>
            @else
                @include('livewire.agent-portal.partials.score-card', [
                    'score'     => $nextScore,
                    'eyebrow'   => 'الهدف القادم',
                    'metText'   => '✓ حققت كل الشروط — بانتظار تحديث الترقية',
                    'unmetText' => 'تحتاج مزيد من الجهد للوصول إلى ' . ($nextClub?->club_name ?? 'النادي القادم'),
                    'variant'   => 'next',
                ])
            @endif
        </div>

    </div>

    {{-- فرصك المكتسبة --}}
    @php
        $opps      = $agent->opportunities()->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $entryOpps = (int)($opps['entry'] ?? 0);
        $firstOpps = (int)($opps['first_arrival'] ?? 0);
        $bonusOpps = (int)($opps['bonus'] ?? 0);
        $maintOpps = (int)($opps['maintenance'] ?? 0);
        $totalOpps = $entryOpps + $firstOpps + $bonusOpps + $maintOpps;
    @endphp
    <div class="card card-pad" style="margin-top:18px;">
        <div class="section-head" style="margin:0 0 14px;">
            <h2>فرصك المكتسبة</h2>
            <span class="meta" style="font-size:18px;font-weight:800;color:var(--primary);">{{ $totalOpps }} فرصة</span>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach([
                ['label' => 'فرص الدخول',   'val' => $entryOpps, 'color' => '#0ea5e9', 'bg' => '#e0f2fe'],
                ['label' => 'أول وصول',      'val' => $firstOpps, 'color' => '#f59e0b', 'bg' => '#fffbeb'],
                ['label' => 'فرص إضافية',    'val' => $bonusOpps, 'color' => '#8b5cf6', 'bg' => '#faf5ff'],
                ['label' => 'المحافظة شهرية',  'val' => $maintOpps, 'color' => '#10b981', 'bg' => '#f0fdf4'],
            ] as $row)
                @if($row['val'] > 0)
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:{{ $row['bg'] }};border-radius:10px;">
                        <span style="font-size:13px;font-weight:500;color:#475569;">{{ $row['label'] }}</span>
                        <span style="font-size:16px;font-weight:800;color:{{ $row['color'] }};">{{ $row['val'] }}</span>
                    </div>
                @endif
            @endforeach
            @if($totalOpps === 0)
                <div style="text-align:center;padding:24px;color:var(--slate-400);font-size:13px;">
                    لم تكسب فرص سحب بعد — انضم لنادٍ لتبدأ
                </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // فتح/إغلاق الـ popover بالنقر (للموبايل، حيث hover غير متاح)
    document.querySelectorAll('[data-journey-toggle]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const station = btn.closest('.journey-station');
            const wasOpen = station.classList.contains('journey-pop-open');
            document.querySelectorAll('.journey-pop-open').forEach((el) => el.classList.remove('journey-pop-open'));
            if (!wasOpen) station.classList.add('journey-pop-open');
        });
    });
    document.addEventListener('click', () => {
        document.querySelectorAll('.journey-pop-open').forEach((el) => el.classList.remove('journey-pop-open'));
    });

    // احتفال بسيط لمرة واحدة عند دخول نادٍ جديد
    const marker = document.querySelector('.journey-marker[data-celebrate-key]');
    if (marker) {
        const key = marker.dataset.celebrateKey;
        if (key && !localStorage.getItem(key)) {
            localStorage.setItem(key, '1');
            const colors = ['#10b981', '#0ea5e9', '#fbbf24', '#f472b6'];
            for (let i = 0; i < 24; i++) {
                const piece = document.createElement('div');
                piece.className = 'journey-confetti-piece';
                piece.style.left = Math.random() * 100 + 'vw';
                piece.style.background = colors[i % colors.length];
                piece.style.animationDelay = (Math.random() * 0.4) + 's';
                document.body.appendChild(piece);
                setTimeout(() => piece.remove(), 3200);
            }
        }
    }

    // عداد تصاعدي للأرقام الكبيرة عند تحميل الصفحة
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    document.querySelectorAll('.js-countup').forEach((el) => {
        const raw = el.textContent.trim();
        const suffix = raw.match(/[^\d.,-]+$/)?.[0] || '';
        const target = parseFloat(raw.replace(suffix, '').replace(/,/g, ''));
        if (isNaN(target) || reduceMotion) return;
        const duration = 900;
        const start = performance.now();
        el.textContent = '0' + suffix;
        function tick(now) {
            const p = Math.min(1, (now - start) / duration);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased).toLocaleString('en-US') + suffix;
            if (p < 1) requestAnimationFrame(tick);
        }
        requestAnimationFrame(tick);
    });
});
</script>
