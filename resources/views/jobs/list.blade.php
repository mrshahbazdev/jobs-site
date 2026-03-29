<x-layout>
    <x-slot name="title">{{ $title ?? 'Latest Jobs' }} - JobsPic</x-slot>

    <div class="mx-auto max-w-7xl px-4 py-12 lg:px-8">
        <!-- Breadcrumbs / Header -->
        <div class="mb-10">
            <nav class="flex mb-4 text-xs font-bold uppercase tracking-widest text-slate-400" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ url('/') }}" class="hover:text-primary transition-colors">Home</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <span class="mx-2 text-slate-300">/</span>
                            <span class="text-slate-500">Jobs</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white md:text-5xl tracking-tight leading-tight">
                {{ $title ?? 'Latest Jobs' }}
            </h1>
            <p class="mt-3 text-lg text-slate-500 dark:text-slate-400 font-medium max-w-3xl">Browse and apply for the most relevant opportunities matching your location and professional criteria.</p>
        </div>

        <div class="grid grid-cols-1 gap-12 lg:grid-cols-4 items-start">
            <!-- Sidebar Filters -->
            <aside class="lg:col-span-1 sticky top-8">
                <x-job-filters :categories="$categories" :cities="$cities" />
            </aside>

            <!-- Job Listings -->
            <main class="lg:col-span-3">
                <div class="space-y-4">
                    @forelse($jobs as $job)
                        <x-job-card :job="$job" />
                    @empty
                        <div class="rounded-3xl border-2 border-dashed border-slate-200 p-20 text-center dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                            <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-6">
                                <span class="material-symbols-outlined text-4xl text-slate-400">search_off</span>
                            </div>
                            <h3 class="text-xl font-black text-slate-900 dark:text-white">No matching jobs found</h3>
                            <p class="mt-2 text-slate-500 dark:text-slate-400 font-medium">We couldn't find any active listings for this category at the moment. Try adjusting your filters or checking back later.</p>
                            <a href="{{ url('/') }}" class="mt-8 inline-flex items-center px-6 py-3 bg-primary text-white font-black rounded-2xl shadow-lg shadow-primary/20 hover:bg-primary/90 transition-all uppercase text-xs tracking-widest">
                                Browse All Jobs
                                <span class="material-symbols-outlined ml-2 text-sm">arrow_forward</span>
                            </a>
                        </div>
                    @endforelse
                </div>

                @if($jobs->hasPages())
                <div class="mt-12 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl shadow-sm">
                    {{ $jobs->links() }}
                </div>
                @endif
            </main>
        </div>
    </div>
</x-layout>

