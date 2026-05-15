<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <meta name="description" content="{{ config('app.name', 'rApanel') }} | Una plataforma moderna para servidores rAthena. ¡Revive los viejos tiempos con tecnología de vanguardia!">
    <meta name="author" content="KhrizPlayCL ~ rApanel Project">
    <meta name="keywords" content="{{ config('app.name', 'rApanel') }}, rAthena, Ragnarok Online, Web Panel, Laravel, Vue3">
    
    <meta property="og:locale" content="es_ES"/>
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ config('app.name', 'rApanel') }}">
    <meta property="og:description" content="Gestiona tu cuenta, vincula tus personajes y mantente al tanto de las novedades del servidor.">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ config('app.name', 'rApanel') }}">
    <!--<meta property="og:image" content="{{ asset('images/web_share.jpg') }}">-->
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <link rel="mask-icon" href="{{ asset('safari-pinned-tab.svg') }}" color="#4A90E2">
    <meta name="msapplication-TileColor" content="#E74C3C">
    <meta name="theme-color" content="#ffffff">

    <title inertia>{{ config('app.name', 'rApanel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="{{ asset('css/ck-editor.css') }}" type="text/css">
    <link rel="stylesheet" href="https://naver.github.io/egjs-flicking-plugins/release/latest/dist/flicking-plugins.css">

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead

    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
</head>

<body class="font-sans antialiased">
    @inertia

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({
            once: true, // Animaciones solo una vez al hacer scroll
            duration: 800 // Velocidad de la animación
        });
    </script>
</body>

</html>
