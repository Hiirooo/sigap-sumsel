<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pemeliharaan Sistem — SIGAP SUMSEL</title>
    <meta name="description" content="Sistem SIGAP SUMSEL sedang dalam pemeliharaan terjadwal. Kami akan segera kembali.">
    <meta name="theme-color" content="#06142c">
    <meta name="application-name" content="SIGAP SUMSEL">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --navy-950: #06142c;
            --navy-900: #0a1628;
            --navy-800: #0c2d5e;
            --navy-700: #1a4d8f;
            --gold: #c9a84c;
            --gold-light: #e8c96d;
            --gold-dark: #b39038;
        }

        html, body { height: 100%; }

        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--navy-950);
            color: #ffffff;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* ---------- Layered backdrop ---------- */
        .backdrop { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        .backdrop__photo {
            position: absolute; inset: 0;
            background-image: url('/images/login-kantor.png');
            background-size: cover; background-position: center;
            opacity: .32;
        }
        .backdrop__photo::after {
            content: ''; position: absolute; inset: 0;
            background:
                linear-gradient(115deg, rgba(6,20,44,.97) 0%, rgba(6,20,44,.9) 42%, rgba(6,20,44,.38) 78%, rgba(6,20,44,.78) 100%);
        }
        .backdrop__landmark {
            position: absolute; left: 50%; bottom: -6px; transform: translateX(-50%);
            width: min(720px, 92vw); height: auto;
            opacity: .5; filter: saturate(1.1);
        }
        .backdrop__landmark svg { display: block; width: 100%; height: auto; }
        .backdrop__glow {
            position: absolute; top: 26%; right: -9rem;
            width: 30rem; height: 30rem; border-radius: 9999px;
            background: radial-gradient(circle, rgba(26,77,143,.45), transparent 65%);
            filter: blur(60px);
        }
        .backdrop__glow--gold {
            position: absolute; bottom: 6%; left: -8rem;
            width: 24rem; height: 24rem; border-radius: 9999px;
            background: radial-gradient(circle, rgba(201,168,76,.16), transparent 65%);
            filter: blur(64px);
        }
        .backdrop__pattern {
            position: absolute; inset: 0; opacity: .5;
            background-image: repeating-linear-gradient(135deg, transparent 0, transparent 13px, rgba(201,168,76,.16) 14px, transparent 15px);
            -webkit-mask-image: linear-gradient(to bottom, transparent 0, #000 30%);
            mask-image: linear-gradient(to bottom, transparent 0, #000 30%);
        }

        .page {
            position: relative; z-index: 1;
            display: flex; min-height: 100dvh; flex-direction: column;
        }

        /* ---------- Header ---------- */
        .header { border-bottom: 1px solid rgba(255,255,255,.10); background: rgba(6,20,44,.55); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); }
        .header__inner {
            max-width: 1280px; margin: 0 auto; width: 100%;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
            padding: 1.15rem 1.5rem;
        }
        .brand { display: flex; align-items: center; gap: .9rem; min-width: 0; text-decoration: none; color: inherit; }
        .brand__mark {
            display: flex; flex-shrink: 0; align-items: center; justify-content: center;
            background: #fff; border-radius: .75rem; padding: .5rem;
            box-shadow: 0 12px 28px rgba(0,0,0,.35); border: 1px solid rgba(255,255,255,.45);
        }
        .brand__mark img { height: 2.4rem; width: auto; display: block; }
        .brand__name { line-height: 1.15; }
        .brand__name strong { display: block; font-size: 1.05rem; font-weight: 800; letter-spacing: -.02em; }
        .brand__name strong em { font-style: normal; font-weight: 500; color: var(--gold-light); }
        .brand__name small { display: block; margin-top: .3rem; font-size: .56rem; font-weight: 700; letter-spacing: .22em; text-transform: uppercase; color: rgba(255,255,255,.55); }
        .header__tag {
            display: none; align-items: center; gap: .5rem;
            font-size: .6rem; font-weight: 700; letter-spacing: .24em; text-transform: uppercase; color: rgba(255,255,255,.5);
        }
        @media (min-width: 640px) { .header__tag { display: inline-flex; } }

        /* ---------- Main ---------- */
        .main {
            flex: 1; display: flex; align-items: center; justify-content: center;
            width: 100%; max-width: 1280px; margin: 0 auto; padding: 4rem 1.5rem 6rem;
        }
        .hero { text-align: center; max-width: 54rem; margin: 0 auto; }

        .status-badge {
            display: inline-flex; align-items: center; gap: .55rem;
            border: 1px solid rgba(201,168,76,.45); background: rgba(201,168,76,.08);
            border-radius: 999px; padding: .5rem 1.05rem;
            font-size: .62rem; font-weight: 800; letter-spacing: .26em; text-transform: uppercase; color: var(--gold-light);
        }
        .status-badge__dot {
            width: .55rem; height: .55rem; border-radius: 999px; background: #34d399;
            box-shadow: 0 0 0 0 rgba(52,211,153,.65); animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52,211,153,.6); }
            70% { box-shadow: 0 0 0 10px rgba(52,211,153,0); }
            100% { box-shadow: 0 0 0 0 rgba(52,211,153,0); }
        }

        .hero__rule { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 2rem; }
        .hero__rule::before, .hero__rule::after { content: ''; height: 1px; width: 4.5rem; background: linear-gradient(90deg, transparent, var(--gold)); }
        .hero__rule::after { transform: scaleX(-1); }
        .hero__rule span { font-size: .62rem; font-weight: 700; letter-spacing: .34em; text-transform: uppercase; color: var(--gold-light); }

        .hero__title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: clamp(2.35rem, 6vw, 4.35rem);
            font-weight: 600; line-height: 1.06; letter-spacing: -.03em;
            margin-top: 1.1rem;
        }
        .hero__title em { font-style: italic; color: var(--gold-light); }

        .hero__desc {
            max-width: 42rem; margin: 1.4rem auto 0;
            font-size: clamp(.95rem, 2vw, 1.05rem); line-height: 1.85; color: rgba(255,255,255,.72);
        }
        .hero__desc strong { color: var(--gold-light); font-weight: 700; }

        /* ---------- Info cards ---------- */
        .cards { display: grid; gap: 1rem; margin-top: 2.75rem; text-align: left; }
        @media (min-width: 640px) { .cards { grid-template-columns: repeat(3, 1fr); } }
        .card {
            position: relative; overflow: hidden;
            border: 1px solid rgba(255,255,255,.12); border-radius: 1.15rem;
            background: rgba(10,22,40,.66); padding: 1.5rem 1.4rem;
            backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
            box-shadow: 0 24px 50px rgba(0,0,0,.28);
            transition: transform .25s ease, border-color .25s ease, background .25s ease;
        }
        .card:hover { transform: translateY(-3px); border-color: rgba(201,168,76,.4); background: rgba(12,45,94,.5); }
        .card::before {
            content: ''; position: absolute; inset: 0 0 auto 0; height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent); opacity: .6;
        }
        .card__icon {
            display: inline-flex; align-items: center; justify-content: center;
            width: 2.6rem; height: 2.6rem; border-radius: .8rem;
            border: 1px solid rgba(201,168,76,.35); background: rgba(201,168,76,.1); color: var(--gold-light);
        }
        .card__icon svg { width: 1.25rem; height: 1.25rem; }
        .card__label { margin-top: 1.1rem; font-size: .6rem; font-weight: 800; letter-spacing: .24em; text-transform: uppercase; color: rgba(255,255,255,.5); }
        .card__value { margin-top: .45rem; font-family: 'Playfair Display', Georgia, serif; font-size: 1.35rem; font-weight: 600; color: #fff; }
        .card__value small { display: block; margin-top: .4rem; font-family: 'Plus Jakarta Sans', sans-serif; font-size: .72rem; font-weight: 500; letter-spacing: 0; text-transform: none; color: rgba(255,255,255,.55); line-height: 1.6; }
        .card__value a { color: var(--gold-light); text-decoration: none; transition: color .2s; }
        .card__value a:hover { color: #fff; text-decoration: underline; }

        /* ---------- Notice ---------- */
        .notice {
            display: flex; align-items: center; gap: .85rem;
            margin-top: 2.1rem; padding: 1.05rem 1.35rem;
            border: 1px dashed rgba(201,168,76,.4); border-radius: .9rem;
            background: rgba(201,168,76,.06); font-size: .78rem; line-height: 1.7; color: rgba(255,255,255,.7);
            text-align: left;
        }
        .notice svg { flex-shrink: 0; color: var(--gold-light); }

        /* ---------- Footer ---------- */
        .footer { border-top: 1px solid rgba(255,255,255,.10); background: rgba(6,20,44,.6); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .footer__inner {
            max-width: 1280px; margin: 0 auto; width: 100%;
            display: flex; flex-direction: column; gap: .4rem; align-items: center; justify-content: space-between;
            padding: 1.15rem 1.5rem; text-align: center;
            font-size: .6rem; font-weight: 600; letter-spacing: .18em; text-transform: uppercase; color: rgba(255,255,255,.4);
        }
        @media (min-width: 640px) { .footer__inner { flex-direction: row; text-align: left; } }
        .footer__inner .motto { color: rgba(201,168,76,.75); }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }
    </style>
</head>
<body>
    <div class="backdrop" aria-hidden="true">
        <div class="backdrop__photo"></div>
        <div class="backdrop__glow"></div>
        <div class="backdrop__glow--gold"></div>
        <div class="backdrop__pattern"></div>
        <img class="backdrop__landmark" src="/images/ampera-sumsel.svg" alt="">
    </div>

    <div class="page">
        <header class="header">
            <div class="header__inner">
                <a class="brand" href="/" aria-label="Beranda SIGAP SUMSEL">
                    <span class="brand__mark"><img src="/favicon.svg" alt="Logo SIGAP SUMSEL"></span>
                    <span class="brand__name">
                        <strong>SIGAP <em>SUMSEL</em></strong>
                        <small>Akurat · Responsif · Mantap</small>
                    </span>
                </a>
                <span class="header__tag">Portal Pemerintah Provinsi Sumatera Selatan</span>
            </div>
        </header>

        <main class="main">
            <section class="hero">
                <span class="status-badge"><span class="status-badge__dot"></span>Pemeliharaan Terjadwal</span>

                <div class="hero__rule"><span>Sedang Berlangsung</span></div>

                <h1 class="hero__title">Sistem sedang<br><em>dalam pemeliharaan.</em></h1>

                <p class="hero__desc">
                    Kami sedang melakukan pembaruan dan peningkatan layanan untuk menjaga
                    <strong>keakuratan, kecepatan, dan keandalan</strong> SIGAP SUMSEL.
                    Mohon kembali lagi beberapa saat — seluruh data Anda tetap aman dan tersimpan.
                </p>

                <div class="cards">
                    <div class="card">
                        <span class="card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </span>
                        <p class="card__label">Estimasi Selesai</p>
                        <p class="card__value">Segera kembali
                            <small>Jadwal pemeliharaan diinformasikan oleh admin sistem</small>
                        </p>
                    </div>

                    <div class="card">
                        <span class="card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                        </span>
                        <p class="card__label">Layanan Terdampak</p>
                        <p class="card__value">Sementara
                            <small>Rilis berita, galeri dokumentasi, kliping media, dan akses portal internal</small>
                        </p>
                    </div>

                    <div class="card">
                        <span class="card__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </span>
                        <p class="card__label">Butuh Bantuan?</p>
                        <p class="card__value"><a href="mailto:humas@sumselprov.go.id">humas@sumselprov.go.id</a>
                            <small>Biro Humas dan Protokol Pemerintah Provinsi Sumatera Selatan</small>
                        </p>
                    </div>
                </div>

                <div class="notice">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Untuk informasi resmi dan berita terkini Provinsi Sumatera Selatan, silakan kunjungi <strong>sumselprov.go.id</strong>.</span>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="footer__inner">
                <span>© {{ date('Y') }} Pemerintah Provinsi Sumatera Selatan</span>
                <span class="motto">Akurat · Responsif · Mantap</span>
                <span>Biro Humas dan Protokol</span>
            </div>
        </footer>
    </div>
</body>
</html>
