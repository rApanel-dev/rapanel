<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    {{-- Dark mode script must run before any CSS to prevent flash of unstyled content --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>

    {{-- Theming en runtime: inyecta las CSS vars del tema guardado ANTES del CSS
         (anti-FOUC). Si no hay tema guardado, no se emite nada y Tailwind usa los
         fallbacks de tailwind.config.js → idéntico a una instalación limpia. --}}
    @php $themeJson = !empty(($siteSettings ?? [])['theme']) ? json_decode($siteSettings['theme'], true) : null; @endphp
    @if($themeJson)
    <style id="rapanel-theme">:root{ {!! \App\Support\Theme::cssVars($themeJson) !!} }</style>
    @endif

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $localeMap   = ['en' => 'en_US', 'es' => 'es_ES', 'pt' => 'pt_BR', 'fr' => 'fr_FR'];
        $ogLocale    = $localeMap[app()->getLocale()] ?? 'en_US';
        $fv          = fn(string $f): int => file_exists(public_path($f)) ? filemtime(public_path($f)) : 1;
        $st          = $siteSettings ?? [];
        $seoTitle    = !empty($st['seo_title'])       ? $st['seo_title']       : config('app.name', 'rApanel');
        $seoDesc     = !empty($st['seo_description']) ? $st['seo_description'] : 'Panel de control para servidores Ragnarok Online rAthena/Hercules.';
        $themeColor  = !empty($st['seo_theme_color']) ? $st['seo_theme_color'] : '#3b82f6';
        $ogImageUrl  = !empty($st['seo_og_image'])
                        ? asset('storage/' . $st['seo_og_image'])
                        : asset('images/web_share.jpg') . '?v=' . $fv('images/web_share.jpg');
        $faviconUrl  = !empty($st['favicon'])
                        ? asset('storage/' . $st['favicon'])
                        : null;
        $appUrl      = config('app.url');
        $jsonLd      = json_encode([
            '@context'            => 'https://schema.org',
            '@type'               => 'WebApplication',
            'name'                => $seoTitle,
            'url'                 => $appUrl,
            'description'         => $seoDesc,
            'applicationCategory' => 'GameApplication',
            'operatingSystem'     => 'Web Browser',
            'inLanguage'          => ['en', 'es', 'pt', 'fr'],
            'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp

    {{-- Google Search Console --}}
    @if(!empty($st['google_site_verification']))
    <meta name="google-site-verification" content="{{ $st['google_site_verification'] }}">
    @endif

    {{-- Primary meta --}}
    <title inertia>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="author" content="KhrizPlayCL ~ rApanel Project">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $seoTitle }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:alt" content="{{ $seoTitle }} — Ragnarok Online Server Panel">

    {{-- Twitter / X Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">
    <meta name="twitter:image:alt" content="{{ $seoTitle }} — Ragnarok Online Server Panel">

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">{!! $jsonLd !!}</script>

    {{-- Favicons & PWA --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}?v={{ $fv('icons/apple-touch-icon.png') }}">
    @if($faviconUrl)
        <link rel="icon" href="{{ $faviconUrl }}">
    @else
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}?v={{ $fv('images/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}?v={{ $fv('images/favicon-16x16.png') }}">
    @endif
    <link rel="manifest" href="{{ asset('manifest.json') }}?v={{ $fv('manifest.json') }}">
    <link rel="mask-icon" href="{{ asset('icons/safari-pinned-tab.svg') }}?v={{ $fv('icons/safari-pinned-tab.svg') }}" color="{{ $themeColor }}">
    <meta name="msapplication-TileColor" content="{{ $themeColor }}">
    <meta name="theme-color" content="{{ $themeColor }}">

    {{-- Resource hints for external CDNs --}}
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>

    {{-- Fonts & CSS --}}
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600|rajdhani:500,600,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/ck-editor.css') }}" type="text/css">

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia

</body>

</html>
