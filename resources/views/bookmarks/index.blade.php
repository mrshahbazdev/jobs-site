<x-layout>
    @section('title', 'My Bookmarked Jobs | JobsPic')

    <main class="mx-auto flex flex-col lg:flex-row w-full max-w-7xl grow gap-8 px-4 py-8 lg:px-10">
        <!-- Sidebar -->
        <aside class="w-full lg:w-1/4 flex flex-col gap-8 no-print">
            <!-- Filter Sidebar (Partial or simplified) -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 shadow-sm sticky top-8">
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">bookmark</span>
                    My Account
                </h3>
                
                <div class="flex flex-col gap-2">
                    <a href="{{ route('bookmarks.index') }}" class="flex items-center gap-3 p-3 rounded-xl bg-primary/10 text-primary font-bold">
                        <span class="material-symbols-outlined text-sm">bookmark</span>
                        Saved Jobs
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 font-medium">
                        <span class="material-symbols-outlined text-sm">person</span>
                        Profile Settings
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="w-full lg:w-3/4 flex flex-col gap-8">
            <header class="flex flex-col gap-2">
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Saved Jobs</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">You have bookmarked {{ $bookmarks->total() }} jobs.</p>
            </header>

            <div class="grid grid-cols-1 gap-6">
                @forelse($bookmarks as $job)
                    <div class="group relative bg-white dark:bg-slate-900 border {{ $job->is_premium ? 'border-amber-400 dark:border-amber-500/50 bg-amber-50/30' : 'border-slate-200 dark:border-slate-800' }} rounded-3xl p-5 md:p-6 transition-all hover:shadow-xl hover:-translate-y-1 flex flex-col md:flex-row gap-6 shadow-sm overflow-hidden">
                        
                        @if($job->is_premium)
                        <div class="absolute top-0 right-0">
                            <div class="bg-amber-400 text-amber-950 text-[10px] font-black px-4 py-1 rounded-bl-2xl uppercase tracking-widest shadow-sm">Premium</div>
                        </div>
                        @elseif($job->is_featured)
                        <div class="absolute top-0 right-0">
                            <div class="bg-sky-500 text-white text-[10px] font-black px-4 py-1 rounded-bl-2xl uppercase tracking-widest shadow-sm">Featured</div>
                        </div>
                        @endif

                        <div class="w-16 h-16 md:w-20 md:h-20 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center shrink-0 shadow-inner group-hover:bg-primary/10 transition-colors">
                            <span class="material-symbols-outlined text-3xl text-slate-400 group-hover:text-primary transition-colors">work</span>
                        </div>
                        
                        <div class="flex flex-col flex-grow">
                            <div class="flex flex-wrap gap-2 mb-2">
                                <span class="text-[10px] font-black uppercase tracking-widest text-primary bg-primary/10 px-2 py-0.5 rounded-md">{{ $job->category->name }}</span>
                                @if($job->job_type)
                                <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20 px-2 py-0.5 rounded-md border border-emerald-100 dark:border-emerald-800/50">{{ $job->job_type }}</span>
                                @endif
                            </div>
                            
                            <h3 class="text-xl font-black text-slate-900 dark:text-white group-hover:text-primary transition-colors leading-tight mb-2">
                                <a href="{{ url('/jobs/'.$job->slug) }}">{{ $job->title }}</a>
                            </h3>
                            
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-medium text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">location_on</span> {{ $job->city->name }}</span>
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">calendar_today</span> {{ $job->created_at->diffForHumans() }}</span>
                                @if($job->experience)
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">military_tech</span> {{ $job->experience }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex md:flex-col justify-end gap-3 mt-4 md:mt-0 items-center md:items-end">
                            <form action="{{ route('bookmarks.toggle', $job->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-3 bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-2xl transition-all shadow-sm group/btn">
                                    <span class="material-symbols-outlined text-xl group-hover/btn:fill-current">delete</span>
                                </button>
                            </form>
                            <a href="{{ url('/jobs/'.$job->slug) }}" class="flex-grow md:flex-grow-0 px-6 py-3 bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 font-black rounded-2xl hover:bg-primary dark:hover:bg-primary hover:text-white transition-all shadow-md text-sm text-center">
                                View Details
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl p-12 text-center">
                        <div class="w-20 h-20 bg-slate-50 dark:bg-slate-800/50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <span class="material-symbols-outlined text-4xl text-slate-300">bookmark_add</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">No saved jobs yet</h3>
                        <p class="text-slate-500 mb-8 max-w-sm mx-auto">Click the bookmark icon on any job to save it for later viewing.</p>
                        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-primary text-white font-black rounded-2xl shadow-xl hover:shadow-primary/20 transition-all">
                            Browse Jobs <span class="material-symbols-outlined">arrow_forward</span>
                        </a>
                    </div>
                @endforelse

                <div class="mt-8">
                    {{ $bookmarks->links() }}
                </div>
            </div>
        </div>
    </main>
</x-layout>
