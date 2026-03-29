<!-- Block: Latest Jobs List -->
<main class="mx-auto flex flex-col lg:flex-row w-full max-w-7xl grow gap-8 py-8">
    @if($block->show_sidebar ?? true)
    <aside class="w-full lg:w-64 shrink-0 flex-col gap-4 flex">
        <!-- Mobile Filter Toggle -->
        <button id="mobileFilterBtn" class="lg:hidden w-full flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white py-3 text-sm font-bold text-slate-700 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 transition-all hover:bg-slate-50">
            <span class="material-symbols-outlined uppercase p-0 m-0 w-auto tracking-wide text-[18px]">filter_list</span>
            Show Filters
        </button>

        <!-- Filter Sidebar -->
        <div id="filterSidebar" class="hidden lg:flex flex-col gap-6 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-bold">Refine Results</h3>
                <a href="{{ url('/') }}" class="text-xs font-medium text-primary hover:underline">Clear all</a>
            </div>
            <div class="flex flex-col gap-4">
                <form action="{{ url('/') }}" method="GET" id="filterForm" class="flex flex-col gap-4">
                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">City</p>
                        <select name="city_id" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300 text-sm focus:ring-primary dark:bg-slate-800 dark:border-slate-700">
                            <option value="">All Cities</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Salary Range (PKR)</p>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="number" name="salary_min" placeholder="Min" value="{{ request('salary_min') }}" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300 text-sm focus:ring-primary dark:bg-slate-800 dark:border-slate-700">
                            <input type="number" name="salary_max" placeholder="Max" value="{{ request('salary_max') }}" onchange="this.form.submit()" class="w-full rounded-xl border-slate-300 text-sm focus:ring-primary dark:bg-slate-800 dark:border-slate-700">
                        </div>
                    </div>

                    <x-whatsapp-alert />
                </form>
            </div>
        </div>
    </aside>
    @endif

    <div class="flex flex-1 flex-col gap-8">
        <div class="flex flex-col gap-3">
            <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Latest Recommended Jobs <span class="current-year"></span></h1>
            <p class="text-base text-slate-500 dark:text-slate-400 leading-relaxed max-w-3xl">Hand-picked opportunities verified for quality and relevance.</p>
        </div>

        <section class="flex flex-col gap-4">
            <div class="grid grid-cols-1 gap-4">
                @foreach($latestJobs as $job)
                    <x-job-card :job="$job" />
                @endforeach
            </div>
            @if($latestJobs->hasPages())
                <div class="mt-10">
                    {{ $latestJobs->links() }}
                </div>
            @endif
            <div class="mt-4 flex justify-center">
                <a href="{{ url('/archive/'.date('Y').'/'.date('m')) }}" class="inline-flex items-center gap-2 text-primary font-black uppercase text-xs tracking-widest hover:underline">
                    Browse Full Archive 
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </section>
    </div>
</main>
