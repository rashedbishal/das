{{--
    Shared layout for the error pages (404, 500, 503 maintenance, ...), in the
    storefront's look (MissingPage.svelte).

    Fully self-contained on purpose: inline CSS, no Vite assets, no database,
    session or shared Inertia props. Errors can happen before or without any of
    those, and deploys pre-render the 503 page into a static file
    (`artisan down --render="errors::503"`) that is served while the app's
    files, including the build, are being replaced. The only external file is
    the small logo in public/ (not part of the build).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    @hasSection('refresh')
        <meta http-equiv="refresh" content="@yield('refresh')">
    @endif
    <title>@yield('title') | {{ config('app.name', 'DAS') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        :root {
            --background: #ffffff;
            --foreground: #1e3a39;
            --primary: #2f3190;
            --primary-faded: rgba(47, 49, 144, 0.2);
            --primary-hover: #26287a;
            --secondary: #eef1ff;
            --muted-foreground: #5b5f8f;
            --accent: #fff1f2;
            --accent-foreground: #d7262f;
            --border: #e6e8f7;
            --ease-soft: cubic-bezier(0.2, 0.7, 0.2, 1);
            color-scheme: light;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-height: 100dvh;
            background: var(--background);
            color: var(--foreground);
            font-family: 'Manrope', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
            overflow-wrap: break-word;
        }
        .shell { width: min(1200px, calc(100% - 40px)); margin-inline: auto; }
        header { border-bottom: 1px solid var(--border); }
        header .shell { display: flex; align-items: center; height: 86px; }
        header img { display: block; height: 40px; width: auto; }
        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 72px 0 88px;
            text-align: center;
        }
        .content { max-width: 560px; margin-inline: auto; }
        .eyebrow {
            margin: 0;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--primary);
        }
        .code {
            margin-top: 20px;
            font-size: 120px;
            font-weight: 600;
            line-height: 1;
            letter-spacing: -2px;
            color: var(--primary-faded);
            font-variant-numeric: tabular-nums;
        }
        h1 { margin: 20px 0 0; font-size: 42px; font-weight: 500; line-height: 1.2; }
        .intro { margin: 12px 0 0; color: var(--muted-foreground); line-height: 1.8; }
        .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-top: 32px; }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 44px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            font: inherit;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        }
        .button-primary { background: var(--primary); color: #ffffff; }
        .button-primary:hover { background: var(--primary-hover); }
        .button-outline { border-color: var(--border); background: var(--background); color: var(--foreground); }
        .button-outline:hover { background: var(--accent); color: var(--accent-foreground); }
        .button:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; }
        .button svg { width: 16px; height: 16px; flex-shrink: 0; }
        .note {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 28px;
            padding: 8px 14px;
            border-radius: 999px;
            background: var(--secondary);
            color: var(--muted-foreground);
            font-size: 11px;
        }
        .note-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--primary);
        }
        footer { border-top: 1px solid var(--border); padding: 22px 0; color: var(--muted-foreground); font-size: 11px; text-align: center; }

        /* The site's "rise" entrance (motion.css), one line after another. */
        @media (prefers-reduced-motion: no-preference) {
            .rise { animation: rise 0.65s var(--ease-soft) both; animation-delay: calc(var(--i, 0) * 110ms); }
            .note-dot { animation: pulse 1.6s ease-in-out infinite alternate; }
        }
        @keyframes rise { from { opacity: 0; translate: 0 16px; } }
        @keyframes pulse { to { opacity: 0.25; } }

        @media (max-width: 767px) {
            header .shell { justify-content: center; height: 72px; }
            header img { height: 34px; }
            main { padding: 48px 0 64px; }
            .code { font-size: 88px; }
            h1 { font-size: 30px; }
            .actions { flex-direction: column; align-items: stretch; }
        }
    </style>
</head>
<body>
    <header>
        <div class="shell">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name', 'DAS') }} home">
                <img src="{{ asset('logo-email.png') }}" width="300" height="123" alt="{{ config('app.name', 'DAS') }} logo">
            </a>
        </div>
    </header>

    <main>
        <div class="shell content">
            <p class="eyebrow rise">@yield('eyebrow')</p>
            <div class="code rise" style="--i: 1" aria-hidden="true">@yield('code')</div>
            <h1 class="rise" style="--i: 2">@yield('heading')</h1>
            <p class="intro rise" style="--i: 3">@yield('message')</p>
            @hasSection('actions')
                <div class="actions rise" style="--i: 4">@yield('actions')</div>
            @endif
            @hasSection('note')
                <div><p class="note rise" style="--i: 5"><span class="note-dot" aria-hidden="true"></span>@yield('note')</p></div>
            @endif
        </div>
    </main>

    <footer>
        <div class="shell">&copy; {{ date('Y') }} {{ config('app.name', 'DAS') }}. Error @yield('code').</div>
    </footer>
</body>
</html>
