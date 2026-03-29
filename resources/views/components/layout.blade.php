<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Latest Govt & Private Jobs in Pakistan - JobsPic')</title>
    <meta name="description" content="@yield('meta_description', 'Find the latest Government, Federal, Police, and Private sector jobs in Pakistan. Daily updated job listings, syllabus, and online apply guides.')">
    <meta name="keywords" content="@yield('meta_keywords', 'jobs in pakistan, govt jobs, private jobs, pakistani jobs')">
    <meta name="theme-color" content="#1773cf">
    
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('og_title', 'Latest Govt & Private Jobs in Pakistan - JobsPic')">
    <meta property="og:description" content="@yield('meta_description', 'Find the latest Government, Federal, Police, and Private sector jobs in Pakistan.')">
    <meta property="og:image" content="@yield('og_image', asset('icons/icon-512x512.png'))">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="@yield('og_title', 'Latest Govt & Private Jobs in Pakistan - JobsPic')">
    <meta property="twitter:description" content="@yield('meta_description', 'Find the latest Government, Federal, Police, and Private sector jobs in Pakistan.')">
    <meta property="twitter:image" content="@yield('og_image', asset('icons/icon-512x512.png'))">

    <!-- Breadcrumb Schema -->
    @stack('breadcrumb_schema')
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Scripts & Styles -->
    <script>
        // Force Light Mode always
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    </script>
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
    
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    </noscript>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
    @stack('extra_head')
    {!! $settings['header_tags'] ?? '' !!}
</head>
<body class="bg-background-light text-slate-900 font-display transition-colors duration-300">
    <div class="relative flex min-h-screen flex-col">
        <x-header />

        {{ $slot }}

        {!! $settings['ad_footer'] ?? '' !!}
        <x-footer />
    </div>

    <script>
        const currentYear = new Date().getFullYear();
        document.querySelectorAll('.current-year').forEach(el => el.textContent = currentYear);

        // Mobile Menu Toggle Logic
        const menuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
                mobileMenu.classList.toggle('flex');
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
