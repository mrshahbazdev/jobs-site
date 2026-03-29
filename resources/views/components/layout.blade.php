<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Latest Govt & Private Jobs in Pakistan - JobsPic')</title>
    <meta name="description" content="@yield('meta_description', 'Find the latest Government, Federal, Police, and Private sector jobs in Pakistan. Daily updated job listings, syllabus, and online apply guides.')">
    <meta name="keywords" content="@yield('meta_keywords', 'jobs in pakistan, govt jobs, private jobs, pakistani jobs')">
    <meta name="theme-color" content="#1773cf">
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Scripts & Styles -->
    <script>
        // Force Light Mode always
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
    </script>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" as="style">
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" as="style">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&display=swap" rel="stylesheet">
    </noscript>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
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
