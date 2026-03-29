<x-layout>
    @section('title', 'Latest ' . $category->name . ' Jobs in Pakistan 2026 - Apply Online | JobsPic')
    @section('meta_description', 'Browse the newest ' . $category->name . ' job openings in Pakistan. View salary, requirements, and application deadlines for ' . $category->name . ' positions.')
    
    @push('breadcrumb_schema')
    <script type="application/ld+json">
    {
      "@@context": "https://schema.org",
      "@@type": "BreadcrumbList",
      "itemListElement": [{
        "@@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ url('/') }}"
      },{
        "@@type": "ListItem",
        "position": 2,
        "name": "Categories",
        "item": "{{ url('/categories') }}"
      },{
        "@@type": "ListItem",
        "position": 3,
        "name": "{{ $category->name }}",
        "item": "{{ url()->current() }}"
      }]
    }
    </script>
    @endpush

    <main class="mx-auto flex flex-col lg:flex-row w-full max-w-7xl grow gap-8 px-4 py-8 lg:px-10">
        <aside class="w-full lg:w-64 shrink-0 flex-col gap-4 flex">
            <div id="filterSidebar" class="flex flex-col gap-6 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-lg font-bold">Filters</h3>
                <form action="{{ url()->current() }}" method="GET" class="flex flex-col gap-4">
                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600">City</p>
                        <select name="city_id" aria-label="City" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 text-sm focus:ring-primary dark:bg-slate-800 dark:border-slate-700">
                            <option value="">All Cities</option>
                            @foreach($cities as $city)
                                <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if($jobTypes->count() > 0)
                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600">Job Type</p>
                        <div class="flex flex-col gap-2">
                            @foreach($jobTypes as $type)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input name="job_type[]" value="{{ $type }}" onchange="this.form.submit()" class="rounded border-slate-300 text-primary focus:ring-primary dark:bg-slate-800 dark:border-slate-700" type="checkbox" {{ in_array($type, (array)request('job_type')) ? 'checked' : '' }}>
                                <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-primary">{{ $type }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if($experiences->count() > 0)
                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-600">Experience</p>
                        <div class="flex flex-col gap-2">
                            @foreach($experiences as $exp)
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <input name="experience[]" value="{{ $exp }}" onchange="this.form.submit()" class="rounded border-slate-300 text-primary focus:ring-primary dark:bg-slate-800 dark:border-slate-700" type="checkbox" {{ in_array($exp, (array)request('experience')) ? 'checked' : '' }}>
                                <span class="text-sm text-slate-600 dark:text-slate-400 group-hover:text-primary">{{ $exp }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </form>

                <hr class="border-slate-100 dark:border-slate-800">

                <h3 class="text-lg font-bold">Other Categories</h3>
                <div class="flex flex-col gap-2">
                    @foreach($categories as $cat)
                        <a href="{{ url('/categories/'.$cat->slug) }}" class="text-sm {{ $cat->id == $category->id ? 'text-primary font-bold' : 'text-slate-600 dark:text-slate-400' }} hover:text-primary">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </aside>

        <div class="flex flex-1 flex-col gap-8">
            <div class="flex flex-col gap-3">
                <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white tracking-tight">Latest {{ $category->name }} Jobs</h1>
                <p class="text-base text-slate-500 dark:text-slate-400 leading-relaxed">Browse all current openings in the {{ $category->name }} sector across Pakistan.</p>
            </div>

            <section class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-4">
                    @forelse($jobs as $job)
                        <div class="group relative flex flex-col gap-4 rounded-xl border {{ $job->is_premium ? 'border-amber-400 bg-amber-50/30' : 'border-slate-200 bg-white' }} p-5 shadow-sm transition-all hover:border-primary/50 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start justify-between">
                                <div class="flex gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                                        <span class="material-symbols-outlined text-primary" aria-hidden="true">work</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            @if($job->is_premium)
                                                <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-2 py-0.5 rounded uppercase border border-amber-200">Premium</span>
                                            @elseif($job->is_featured)
                                                <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-2 py-0.5 rounded uppercase border border-blue-200">Featured</span>
                                            @endif
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900 group-hover:text-primary dark:text-white transition-colors">{{ $job->title }}</h3>
                                        <p class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ $job->city->name }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 text-[10px] font-bold uppercase text-slate-500">
                                @if($job->job_type) <span class="bg-slate-100 px-2 py-1 rounded">{{ $job->job_type }}</span> @endif
                                @if($job->experience) <span class="bg-slate-100 px-2 py-1 rounded">{{ $job->experience }}</span> @endif
                            </div>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs text-slate-500">Deadline: {{ $job->deadline ? \Carbon\Carbon::parse($job->deadline)->format('M d, Y') : 'Ongoing' }}</span>
                                <a href="{{ url('/jobs/'.$job->slug) }}" class="rounded bg-primary px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-primary/90 transition-colors">Details</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-20 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                            <p class="text-slate-500">No jobs found in this category yet. Check back later!</p>
                        </div>
                    @endforelse
                </div>
                
                <div class="mt-8">
                    {{ $jobs->links() }}
                </div>
            </section>

            <!-- SEO Content Section -->
            <hr class="border-slate-100 dark:border-slate-800 my-4">
            <section class="prose prose-slate dark:prose-invert max-w-none bg-slate-50 dark:bg-slate-900/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                <h2 class="text-xl font-bold mb-4">About {{ $category->name }} Jobs in Pakistan</h2>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    Find the latest <strong>{{ $category->name }} job openings</strong> across Pakistan for 2026. We collect daily advertisements from major newspapers like Jang, Express, and Dawn to bring you the best opportunities in the {{ $category->name }} sector. Whether you are looking for Government, Semi-Government, or Private sector positions, our categorized listings help you find your dream career quickly. 
                </p>
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-400 mt-2">
                    Stay updated with the newest <strong>{{ $category->name }} vacancies</strong> by joining our WhatsApp alerts. We provide detailed information including salary range, education requirements (Matric, Inter, Graduate, Masters), and application deadlines for all {{ $category->name }} listings.
                </p>
            </section>
        </div>
    </main>
</x-layout>
