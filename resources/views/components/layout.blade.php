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
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#1773cf",
                        "background-light": "#f6f7f8",
                        "background-dark": "#111921",
                    },
                    fontFamily: {
                        "display": ["Inter"]
                    },
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .material-symbols-outlined { font-size: 20px; vertical-align: middle; }
    </style>
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
