<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sky Clubs</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@300;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Alexandria', sans-serif;
            background: #080c14;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ── Gradient Orbs ────────────────────────────── */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            animation: orb-drift 22s ease-in-out infinite;
        }
        .orb-1 {
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(14,165,233,.28) 0%, transparent 70%);
            top: -150px; left: 50%; transform: translateX(-50%);
        }
        .orb-2 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(99,102,241,.22) 0%, transparent 70%);
            bottom: -100px; right: -100px;
            animation-duration: 28s;
            animation-direction: reverse;
        }
        .orb-3 {
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(234,179,8,.15) 0%, transparent 70%);
            bottom: 10%; left: 5%;
            animation-duration: 18s;
        }
        @keyframes orb-drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33%       { transform: translate(40px, -30px) scale(1.08); }
            66%       { transform: translate(-30px, 25px) scale(.94); }
        }

        /* ── Stars / Particles ────────────────────────── */
        .particles {
            position: fixed;
            inset: 0;
            pointer-events: none;
            overflow: hidden;
        }
        .particles span {
            position: absolute;
            border-radius: 50%;
            opacity: 0;
            animation: particle-rise linear infinite;
        }
        @keyframes particle-rise {
            0%   { transform: translateY(0) scale(1); opacity: 0; }
            10%  { opacity: 1; }
            90%  { opacity: .6; }
            100% { transform: translateY(-100vh) scale(.3); opacity: 0; }
        }

        /* ── Expand Rings ─────────────────────────────── */
        .rings {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        .ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(14,165,233,.35);
            animation: ring-expand 5s ease-out infinite;
            width: 180px; height: 180px;
        }
        .ring-2 { animation-delay: 1.6s; border-color: rgba(99,102,241,.3); }
        .ring-3 { animation-delay: 3.2s; border-color: rgba(234,179,8,.25); }
        @keyframes ring-expand {
            0%   { transform: scale(1);   opacity: .8; }
            100% { transform: scale(4.5); opacity: 0; }
        }

        /* ── Logo ─────────────────────────────────────── */
        .center { position: relative; z-index: 10; }

        .logo-wrap {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 220px; height: 220px;
        }

        /* Glow pulse behind logo */
        .logo-glow {
            position: absolute;
            inset: -30px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(14,165,233,.45) 0%, transparent 65%);
            animation: glow-pulse 3s ease-in-out infinite;
        }
        @keyframes glow-pulse {
            0%, 100% { transform: scale(1);    opacity: .7; }
            50%       { transform: scale(1.25); opacity: 1; }
        }

        /* Orbiting ring 1 */
        .orbit {
            position: absolute;
            border-radius: 50%;
            border: 1px solid transparent;
            pointer-events: none;
        }
        .orbit-1 {
            width: 280px; height: 280px;
            border-color: rgba(14,165,233,.25);
            animation: orbit-spin 18s linear infinite;
        }
        .orbit-2 {
            width: 340px; height: 340px;
            border-color: rgba(99,102,241,.2);
            animation: orbit-spin 28s linear infinite reverse;
        }
        .orbit-dot {
            position: absolute;
            top: -4px; left: 50%;
            transform: translateX(-50%);
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #0ea5e9;
            box-shadow: 0 0 10px 3px rgba(14,165,233,.7);
        }
        .orbit-2 .orbit-dot {
            background: #6366f1;
            box-shadow: 0 0 10px 3px rgba(99,102,241,.7);
        }
        @keyframes orbit-spin {
            from { transform: rotate(0deg); }
            to   { transform: rotate(360deg); }
        }

        /* Logo image — float */
        .logo {
            position: relative;
            z-index: 2;
            width: 160px;
            height: auto;
            animation: logo-float 4s ease-in-out infinite;
            filter: drop-shadow(0 0 30px rgba(14,165,233,.5));
        }
        @keyframes logo-float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-14px); }
        }

        /* ── Admin link (subtle corner) ───────────────── */
        .admin-link {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 36px; height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.3);
            font-size: 16px;
            text-decoration: none;
            transition: background .2s, color .2s, border-color .2s;
            z-index: 100;
        }
        .admin-link:hover {
            background: rgba(14,165,233,.15);
            border-color: rgba(14,165,233,.4);
            color: #0ea5e9;
        }
    </style>
</head>
<body>

    {{-- Gradient orbs --}}
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>

    {{-- Floating particles --}}
    <div class="particles" id="particles"></div>

    {{-- Expand rings --}}
    <div class="rings">
        <div class="ring"></div>
        <div class="ring ring-2"></div>
        <div class="ring ring-3"></div>
    </div>

    {{-- Logo --}}
    <main class="center">
        <div class="logo-wrap">
            <div class="logo-glow"></div>
            <div class="orbit orbit-1"><span class="orbit-dot"></span></div>
            <div class="orbit orbit-2"><span class="orbit-dot"></span></div>
            <img src="/images/sky-logo.png" class="logo" alt="Sky Clubs">
        </div>
    </main>

    {{-- Admin link --}}
    <a href="{{ url('/admin') }}" class="admin-link">⚙</a>

    <script>
        // Generate particles dynamically
        const colors = ['#0ea5e9','#6366f1','#eab308','#10b981','#0ea5e9','#0ea5e9'];
        const container = document.getElementById('particles');
        const count = 28;
        for (let i = 0; i < count; i++) {
            const s = document.createElement('span');
            const size = Math.random() * 3 + 1.5;
            s.style.cssText = `
                width:${size}px;
                height:${size}px;
                left:${Math.random() * 100}%;
                bottom:${Math.random() * -20}%;
                background:${colors[Math.floor(Math.random() * colors.length)]};
                box-shadow:0 0 ${size*3}px ${colors[Math.floor(Math.random() * colors.length)]};
                animation-duration:${7 + Math.random() * 9}s;
                animation-delay:${Math.random() * 10}s;
            `;
            container.appendChild(s);
        }
    </script>
</body>
</html>
