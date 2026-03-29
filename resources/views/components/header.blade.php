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
                    <input name="q" value="{{ request('q') }}" class="block w-full rounded-lg border-0 bg-slate-100 py-2 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm" placeholder="Search latest jobs..." type="text"/>
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
                <a href="{{ route('jobs.whatsapp') }}" class="hidden sm:inline-flex items-center justify-center rounded-lg bg-[#25D366]/10 px-4 py-2.5 text-sm font-bold text-[#25D366] hover:bg-[#25D366]/20 transition-colors border border-[#25D366]/20 whitespace-nowrap">
                    Join WhatsApp
                </a>
            @endif

            <!-- Hamburger (mobile only) -->
            <button id="mobileMenuBtn" class="md:hidden flex items-center justify-center rounded-lg p-2 text-slate-700 hover:bg-slate-100 transition-colors" aria-label="Open menu">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobileMenu" class="hidden md:hidden absolute left-0 right-0 top-full bg-white border-t border-slate-200 shadow-lg z-50 flex-col">
        <!-- Mobile Search -->
        <div class="px-4 pt-4 pb-2">
            <form action="{{ url('/search') }}" method="GET" class="w-full">
                <label class="relative w-full">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                        <span class="material-symbols-outlined" aria-hidden="true">search</span>
                    </div>
                    <input name="q" value="{{ request('q') }}" class="block w-full rounded-lg border-0 bg-slate-100 py-2.5 pl-10 pr-3 text-slate-900 ring-1 ring-inset ring-slate-200 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-primary text-sm" placeholder="Search latest jobs..." type="text"/>
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

            <!-- WhatsApp button in mobile menu -->
            <a href="{{ route('jobs.whatsapp') }}" class="mt-3 flex items-center justify-center gap-2 bg-[#25D366] text-white px-6 py-3 rounded-xl font-black text-sm uppercase tracking-widest hover:bg-[#1da851] transition-all">
                <span class="material-symbols-outlined text-[18px]">whatsapp</span>
                Join WhatsApp Groups
            </a>
        </nav>
    </div>
</header>
