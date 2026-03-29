<header class="sticky top-0 z-50 w-full border-b border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-background-dark/80 backdrop-blur-md px-4 lg:px-10 py-3">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-8">
        <div class="flex items-center gap-8 flex-1">
            @php
                $logoBlock = $headerBlocks->where('type', 'header_logo')->first();
            @endphp
            
            @if($logoBlock)
                @include('components.blocks.header_logo', ['block' => $logoBlock])
            @else
                <a href="{{ url('/') }}" class="flex items-center gap-2 text-primary hover:opacity-80 transition-opacity">
                    <span class="material-symbols-outlined text-3xl font-bold" aria-hidden="true">work</span>
                    <span class="text-2xl font-black leading-tight tracking-tight text-slate-900 dark:text-slate-100">Jobs<span class="text-primary">Pic</span><span class="text-slate-400 text-lg">.com</span></span>
                </a>
            @endif

            <div class="hidden md:flex flex-1 max-w-md">
                <form action="{{ url('/search') }}" method="GET" class="w-full">
                    <label class="relative w-full">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <span class="material-symbols-outlined" aria-hidden="true">search</span>
                        </div>
                        <input name="q" value="{{ request('q') }}" class="block w-full rounded-lg border-0 bg-slate-100 dark:bg-slate-800 py-2 pl-10 pr-3 text-slate-900 dark:text-slate-100 ring-1 ring-inset ring-slate-200 dark:ring-slate-700 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm" placeholder="Search latest jobs..." type="text"/>
                    </label>
                </form>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @foreach($headerBlocks->where('type', 'nav_link') as $block)
                @include('components.blocks.nav_link', ['block' => $block])
            @endforeach

            @if($headerBlocks->where('type', 'nav_link')->isEmpty())
                <a href="{{ url('/categories') }}" class="hidden lg:inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    Categories
                </a>
                <a href="https://chat.whatsapp.com/your-group-link" class="inline-flex items-center justify-center rounded-lg bg-[#25D366]/10 px-4 sm:px-5 py-2 sm:py-2.5 text-sm font-bold text-[#25D366] hover:bg-[#25D366]/20 transition-colors border border-[#25D366]/20">
                    <span class="hidden sm:inline">Join WhatsApp</span>
                    <span class="sm:hidden">Join</span>
                </a>
            @endif

            <button id="mobileMenuBtn" class="md:hidden flex items-center justify-center rounded-lg p-2 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined" aria-hidden="true">menu</span>
            </button>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div id="mobileMenu" class="hidden md:hidden mt-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-lg p-4 flex-col gap-4 absolute left-4 right-4 top-16 z-50">
        @foreach($headerBlocks->where('type', 'nav_link') as $block)
            @php
                $url = (str_starts_with($block->url ?? '', 'http') || str_contains($block->url ?? '', '/')) ? $block->url : (Route::has($block->url ?? '') ? route($block->url) : $block->url);
            @endphp
            <a href="{{ $url }}" class="font-bold text-slate-700 hover:text-primary dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">{{ $block->title }}</a>
        @endforeach

        @if($headerBlocks->where('type', 'nav_link')->isEmpty())
            <a href="{{ url('/categories') }}" class="font-bold text-slate-700 hover:text-primary dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">All Categories</a>
            <a href="{{ url('/') }}" class="font-bold text-slate-700 hover:text-primary dark:text-slate-200 border-b border-slate-100 dark:border-slate-800 pb-2">Latest Jobs</a>
        @endif
    </div>
</header>
