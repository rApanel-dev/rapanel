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

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $localeMap = ['en' => 'en_US', 'es' => 'es_ES', 'pt' => 'pt_BR', 'fr' => 'fr_FR'];
        $ogLocale  = $localeMap[app()->getLocale()] ?? 'en_US';
        $fv        = fn(string $f): int => file_exists(public_path($f)) ? filemtime(public_path($f)) : 1;
        $ogImgV    = $fv('images/web_share.jpg');
        $appName   = config('app.name', 'rApanel');
        $appUrl    = config('app.url');
    @endphp

    {{-- Primary meta --}}
    <title inertia>{{ $appName }}</title>
    <meta name="description" content="{{ $appName }} — Panel de control para servidores Ragnarok Online rAthena/Hercules.">
    <meta name="author" content="KhrizPlayCL ~ rApanel Project">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $appName }}">
    <meta property="og:description" content="Gestiona tu cuenta, vincula tus personajes y mantente al tanto de las novedades del servidor.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta property="og:image" content="{{ asset('images/web_share.jpg') }}?v={{ $ogImgV }}">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $appName }} — Ragnarok Online Server Panel">

    {{-- Twitter / X Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $appName }}">
    <meta name="twitter:description" content="Gestiona tu cuenta, vincula tus personajes y mantente al tanto de las novedades del servidor.">
    <meta name="twitter:image" content="{{ asset('images/web_share.jpg') }}?v={{ $ogImgV }}">
    <meta name="twitter:image:alt" content="{{ $appName }} — Ragnarok Online Server Panel">

    {{-- JSON-LD Structured Data --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebApplication",
        "name": {!! json_encode($appName) !!},
        "url": {!! json_encode($appUrl) !!},
        "description": "Panel de control para servidores Ragnarok Online rAthena/Hercules.",
        "applicationCategory": "GameApplication",
        "operatingSystem": "Web Browser",
        "inLanguage": ["en", "es", "pt", "fr"],
        "offers": {
            "@type": "Offer",
            "price": "0",
            "priceCurrency": "USD"
        }
    }
    </script>

    {{-- Favicons & PWA --}}
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}?v={{ $fv('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}?v={{ $fv('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}?v={{ $fv('images/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}?v={{ $fv('manifest.json') }}">
    <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}?v={{ $fv('safari-pinned-tab.svg') }}" color="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    <meta name="theme-color" content="#3b82f6">

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
