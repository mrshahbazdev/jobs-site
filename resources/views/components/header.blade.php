<header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md px-4 lg:px-8 py-3">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
        <!-- Logo -->
        <div class="flex items-center gap-4 shrink-0">
            @php
                $logoBlock = $headerBlocks->where('type', 'header_logo')->first();
            @endphp
            
            @if($logoBlock)
                @include('components.blocks.header_logo', ['block' => $logoBlock])
            @else
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-primary hover:opacity-80 transition-opacity">
                    <span class="material-symbols-outlined text-3xl font-bold" aria-hidden="true">work</span>
                    <span class="text-2xl font-black leading-tight tracking-tight text-slate-900">Jobs<span class="text-primary">Pic</span><span class="text-slate-400 text-lg">.com</span></span>
                </a>
            @endif
        </div>

        <!-- Desktop Search Bar -->
        <div class="hidden md:flex flex-1 max-w-md">
            <form action="{{ url('/search') }}" method="GET" class="w-full">
                <label class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    </div>
                    <input name="q" value="{{ request('q') }}" class="block w-full rounded-lg border-0 bg-slate-100 py-2 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-700 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm" placeholder="Search latest jobs..." type="text"/>
                </label>
            </form>
        </div>

        <!-- Desktop Nav + Mobile Hamburger -->
        <div class="flex items-center gap-2 sm:gap-3">
            @foreach($headerBlocks->where('type', 'nav_link') as $block)
                @include('components.blocks.nav_link', ['block' => $block])
            @endforeach

            @if($headerBlocks->where('type', 'nav_link')->isEmpty())
                <a href="{{ url('/categories') }}" class="hidden lg:inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-colors">
                    Categories
                </a>
                <a href="{{ route('jobs.whatsapp') }}" class="hidden sm:inline-flex items-center justify-center rounded-lg bg-[#075E54] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#064e46] transition-colors shadow-sm whitespace-nowrap">
                    Join WhatsApp
                </a>
            @endif

            <div class="hidden sm:inline-flex">
                <x-push-subscribe label="Get Alerts" />
            </div>

            @auth
                <div class="relative hidden sm:block" data-user-menu>
                    <button type="button" data-user-menu-toggle class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200" aria-haspopup="menu" aria-expanded="false">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-black text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="max-w-[120px] truncate">{{ auth()->user()->name }}</span>
                        <span class="material-symbols-outlined text-base" aria-hidden="true">expand_more</span>
                    </button>
                    <div data-user-menu-panel hidden class="absolute right-0 mt-2 w-56 rounded-lg bg-white p-1 shadow-lg ring-1 ring-slate-200">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">dashboard</span>
                            Dashboard
                        </a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">person</span>
                            My profile
                        </a>
                        <a href="{{ url('/bookmarks') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">bookmark</span>
                            Saved jobs
                        </a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ url('/admin') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                                <span class="material-symbols-outlined text-base text-primary" aria-hidden="true">shield_person</span>
                                Admin panel
                            </a>
                        @endif
                        <hr class="my-1 border-slate-200">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50">
                                <span class="material-symbols-outlined text-base" aria-hidden="true">logout</span>
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden lg:inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="hidden sm:inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 transition-colors">
                    Sign up
                </a>
            @endauth

            <!-- Hamburger (mobile only) -->
            <button id="mobileMenuBtn" class="md:hidden flex items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Open menu">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
        </div>
    </div>

    @auth
        @once
            @push('scripts')
                <script>
                    (function () {
                        const container = document.querySelector('[data-user-menu]');
                        if (!container) return;
                        const toggle = container.querySelector('[data-user-menu-toggle]');
                        const panel = container.querySelector('[data-user-menu-panel]');
                        if (!toggle || !panel) return;

                        function setOpen(open) {
                            panel.hidden = !open;
                            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                        }

                        toggle.addEventListener('click', function (e) {
                            e.stopPropagation();
                            setOpen(panel.hidden);
                        });
                        document.addEventListener('click', function (e) {
                            if (!container.contains(e.target)) setOpen(false);
                        });
                        document.addEventListener('keydown', function (e) {
                            if (e.key === 'Escape') setOpen(false);
                        });
                    })();
                </script>
            @endpush
        @endonce
    @endauth

    <!-- Mobile Navigation Menu -->
    <div id="mobileMenu" class="hidden md:hidden absolute left-0 right-0 top-full bg-white border-t border-slate-200 shadow-lg z-50 flex-col">
        <!-- Mobile Search -->
        <div class="px-4 pt-4 pb-2">
            <form action="{{ url('/search') }}" method="GET" class="w-full">
                <label class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    </div>
                    <input name="q" value="{{ request('q') }}" class="block w-full rounded-lg border-0 bg-slate-100 py-2.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-700 focus:ring-2 focus:ring-inset focus:ring-primary text-sm" placeholder="Search latest jobs..." type="text"/>
                </label>
            </form>
        </div>

        <!-- Mobile Nav Links -->
        <nav class="flex flex-col px-4 pb-4 gap-1">
            @foreach($headerBlocks->where('type', 'nav_link') as $block)
                @php
                    $url = (str_starts_with($block->url ?? '', 'http') || str_contains($block->url ?? '', '/')) ? $block->url : (Route::has($block->url ?? '') ? route($block->url) : $block->url);
                @endphp
                <a href="{{ $url }}" class="flex items-center py-2.5 px-2 font-bold text-slate-700 hover:text-primary border-b border-slate-100 text-sm">{{ $block->title }}</a>
            @endforeach

            @if($headerBlocks->where('type', 'nav_link')->isEmpty())
                <a href="{{ url('/categories') }}" class="flex items-center py-2.5 px-2 font-bold text-slate-700 hover:text-primary border-b border-slate-100 text-sm">All Categories</a>
                <a href="{{ url('/') }}" class="flex items-center py-2.5 px-2 font-bold text-slate-700 hover:text-primary border-b border-slate-100 text-sm">Latest Jobs</a>
            @endif

            <a href="{{ route('jobs.whatsapp') }}" class="mt-3 flex items-center justify-center gap-2 bg-[#075E54] text-white px-6 py-3 rounded-xl font-black text-sm uppercase tracking-widest hover:bg-[#064e46] transition-all">
                <span class="material-symbols-outlined text-[18px]">whatsapp</span>
                Join WhatsApp Groups
            </a>

            @auth
                <div class="mt-3 flex flex-col gap-1 border-t border-slate-200 pt-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 py-2.5 px-2 font-bold text-slate-700 hover:text-primary text-sm">
                        <span class="material-symbols-outlined text-base text-primary">dashboard</span>
                        Dashboard
                    </a>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 py-2.5 px-2 font-bold text-slate-700 hover:text-primary text-sm">
                        <span class="material-symbols-outlined text-base text-primary">person</span>
                        My profile ({{ auth()->user()->name }})
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 py-2.5 px-2 text-left font-bold text-rose-600 hover:bg-rose-50 text-sm">
                            <span class="material-symbols-outlined text-base">logout</span>
                            Log out
                        </button>
                    </form>
                </div>
            @else
                <div class="mt-3 flex gap-2 border-t border-slate-200 pt-3">
                    <a href="{{ route('login') }}" class="flex-1 text-center py-2.5 px-4 rounded-lg font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 text-sm">
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="flex-1 text-center py-2.5 px-4 rounded-lg font-bold text-white bg-primary hover:bg-primary/90 text-sm">
                        Sign up
                    </a>
                </div>
            @endauth
        </nav>
    </div>
</header>
