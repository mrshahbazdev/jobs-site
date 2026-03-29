<x-layout>
    <x-slot name="title">Browse Job Categories - JobsPic</x-slot>

    <main class="mx-auto w-full max-w-7xl grow px-4 py-8 lg:px-10">
        <div class="mb-12 text-center">
            <h1 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Explore Job Opportunities</h1>
            <p class="text-lg text-slate-500 dark:text-slate-400 max-w-2xl mx-auto">Browse the latest job openings organized by Government departments, major cities, and industry sectors.</p>
        </div>

        <div class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">account_balance</span>
                    Browse by Category
                </h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($categories as $cat)
                <a href="{{ url('/categories/'.$cat->slug) }}" class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm p-6 hover:shadow-md hover:border-primary/50 transition-all flex flex-col items-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <span class="material-symbols-outlined text-3xl">{{ $cat->icon_name ?? 'work' }}</span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">{{ $cat->name }}</h3>
                    <span class="text-sm font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full">{{ $cat->job_listings_count }} Jobs</span>
                </a>
                @endforeach
            </div>
        </div>

        <div class="mb-16">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">location_city</span>
                    Browse by City
                </h2>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @foreach($cities as $city)
                <a href="{{ url('/cities/'.$city->slug) }}" class="group bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 hover:border-primary transition-colors text-center">
                    <h3 class="font-bold text-slate-800 dark:text-slate-200 group-hover:text-primary">{{ $city->name }}</h3>
                </a>
                @endforeach
            </div>
        </div>
    </main>
</x-layout>
