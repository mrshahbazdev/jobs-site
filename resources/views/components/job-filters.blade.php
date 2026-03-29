@props(['categories' => null, 'cities' => null, 'jobTypes' => null, 'experiences' => null])

<div id="filterSidebar" class="flex flex-col gap-6 rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-50 dark:border-slate-800 pb-4">
        <h3 class="text-lg font-black tracking-tight">Refine Results</h3>
        <a href="{{ url()->current() }}" class="text-xs font-bold text-primary hover:underline">Clear all</a>
    </div>
    
    <div class="flex flex-col gap-6">
        <form action="{{ url()->current() }}" method="GET" id="filterForm" class="flex flex-col gap-6">
            @if($cities && $cities->count() > 0)
            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Location</p>
                <select name="city_id" onchange="this.form.submit()" class="w-full rounded-2xl border-slate-200 text-sm font-bold focus:ring-primary dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                    <option value="">All Cities</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" {{ request('city_id') == $city->id ? 'selected' : '' }}>{{ $city->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($categories && $categories->count() > 0)
            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Category</p>
                <select name="category_id" onchange="this.form.submit()" class="w-full rounded-2xl border-slate-200 text-sm font-bold focus:ring-primary dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($jobTypes && $jobTypes->count() > 0)
            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Job Type</p>
                <div class="flex flex-col gap-2.5">
                    @foreach($jobTypes as $type)
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input name="job_type[]" value="{{ $type }}" onchange="this.form.submit()" class="rounded-lg border-slate-200 text-primary focus:ring-primary dark:bg-slate-800 dark:border-slate-700" type="checkbox" {{ in_array($type, (array)request('job_type')) ? 'checked' : '' }}>
                        <span class="text-sm font-bold text-slate-600 dark:text-slate-400 group-hover:text-primary transition-colors">{{ $type }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="space-y-3">
                <p class="text-xs font-black uppercase tracking-widest text-slate-400">Salary Range (PKR)</p>
                <div class="grid grid-cols-2 gap-2">
                    <input type="number" name="salary_min" placeholder="Min" value="{{ request('salary_min') }}" onchange="this.form.submit()" class="w-full rounded-2xl border-slate-200 text-sm font-bold focus:ring-primary dark:bg-slate-800 dark:border-slate-700">
                    <input type="number" name="salary_max" placeholder="Max" value="{{ request('salary_max') }}" onchange="this.form.submit()" class="w-full rounded-2xl border-slate-200 text-sm font-bold focus:ring-primary dark:bg-slate-800 dark:border-slate-700">
                </div>
            </div>

            <!-- Enhanced WhatsApp Alert CTA inside Sidebar -->
            <div class="bg-gradient-to-br from-emerald-600 to-teal-500 rounded-2xl p-5 text-white shadow-lg overflow-hidden relative group mt-4">
                <div class="absolute -right-4 -bottom-4 opacity-10 group-hover:scale-125 transition-all duration-500">
                    <span class="material-symbols-outlined text-7xl font-bold">chat</span>
                </div>
                <div class="relative z-10">
                    <h4 class="text-md font-black mb-1 flex items-center gap-2">
                        <span class="material-symbols-outlined">whatsapp</span>
                        Fast Alerts
                    </h4>
                    <p class="text-[10px] text-emerald-100 font-bold uppercase tracking-wider mb-4 leading-tight">Get Jobs on WhatsApp</p>
                    <a href="{{ route('jobs.whatsapp') }}" class="block w-full bg-white text-emerald-600 text-center py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-emerald-50 transition-colors shadow-sm">
                        Join Groups
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
