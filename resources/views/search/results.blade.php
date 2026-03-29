<x-layout>
    <x-slot name="title">Search Results for "{{ $query }}" - JobsPic</x-slot>

    <main class="mx-auto flex flex-col lg:flex-row w-full max-w-7xl grow gap-8 px-4 py-8 lg:px-10">
        <aside class="w-full lg:w-64 shrink-0 flex-col gap-4 flex">
            <div class="flex flex-col gap-6 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-lg font-bold">Filter results</h3>
                <div class="space-y-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Categories</p>
                        <div class="flex flex-col gap-2">
                            @foreach($categories->take(8) as $cat)
                                <a href="{{ url('/categories/'.$cat->slug) }}" class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary">
                                    {{ $cat->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex flex-1 flex-col gap-8">
            <div class="flex flex-col gap-3">
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">Search Results for "{{ $query }}"</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400">Found {{ $jobs->total() }} matching jobs.</p>
                
                @if(!empty($aiFilters))
                <div class="mt-2 flex flex-wrap items-center gap-3 p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/50 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400 font-bold text-xs uppercase tracking-widest shrink-0">
                        <span class="material-symbols-outlined text-sm">auto_awesome</span>
                        AI Search interpretation:
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if(!empty($aiFilters['keywords']))
                            <span class="bg-white dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 px-3 py-1 rounded-lg text-[11px] font-black border border-indigo-100 dark:border-indigo-800 shadow-sm">
                                Keywords: "{{ $aiFilters['keywords'] }}"
                            </span>
                        @endif
                        @if(!empty($aiFilters['city']))
                            <span class="bg-white dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 px-3 py-1 rounded-lg text-[11px] font-black border border-emerald-100 dark:border-emerald-800 shadow-sm">
                                City: "{{ $aiFilters['city'] }}"
                            </span>
                        @endif
                        @if(!empty($aiFilters['experience']))
                            <span class="bg-white dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 px-3 py-1 rounded-lg text-[11px] font-black border border-amber-100 dark:border-amber-800 shadow-sm">
                                Experience: "{{ $aiFilters['experience'] }}"
                            </span>
                        @endif
                        @if(!empty($aiFilters['job_type']))
                            <span class="bg-white dark:bg-sky-900/50 text-sky-700 dark:text-sky-300 px-3 py-1 rounded-lg text-[11px] font-black border border-sky-100 dark:border-sky-800 shadow-sm">
                                Type: "{{ $aiFilters['job_type'] }}"
                            </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <section class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-4">
                    @if(count($jobs) > 0)
                        @foreach($jobs as $job)
                            <div class="group relative flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition-all hover:border-primary/50 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                                            <span class="material-symbols-outlined text-primary" aria-hidden="true">search</span>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-slate-900 group-hover:text-primary dark:text-white transition-colors">{{ $job->title }}</h3>
                                            <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ $job->category->name }} • {{ $job->city->name }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 flex items-center justify-end">
                                    <a href="{{ url('/jobs/'.$job->slug) }}" class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-primary/90 transition-colors">View Details</a>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-20 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">search_off</span>
                            <p class="text-slate-500">No jobs matched your search criteria. Try different keywords.</p>
                        </div>
                    @endif
                </div>
                
                <div class="mt-8">
                    {{ $jobs->links() }}
                </div>
            </section>
        </div>
    </main>
</x-layout>
