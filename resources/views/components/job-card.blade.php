@props(['job'])

<div class="group relative flex flex-col gap-4 rounded-xl border {{ $job->is_premium ? 'border-amber-400 bg-amber-50/30' : 'border-slate-200 bg-white' }} p-5 shadow-sm transition-all hover:border-primary/50 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-start justify-between">
        <div class="flex gap-4">
            <div class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 overflow-hidden shadow-sm">
                @if($job->company_logo)
                    <img src="{{ asset('storage/'.$job->company_logo) }}" alt="{{ $job->title }} at {{ $job->company_name ?? $job->department }}" class="w-full h-full object-cover">
                @else
                    <span class="material-symbols-outlined text-primary text-2xl" aria-hidden="true">work</span>
                @endif
            </div>
            <div>
                <div class="flex items-center gap-2">
                    @if($job->is_premium)
                        <span class="bg-amber-100 text-amber-700 text-[10px] font-black px-2 py-0.5 rounded uppercase border border-amber-200">Premium</span>
                    @elseif($job->is_featured)
                        <span class="bg-blue-100 text-blue-700 text-[10px] font-black px-2 py-0.5 rounded uppercase border border-blue-200">Featured</span>
                    @endif
                </div>
                <h2 class="font-bold text-slate-900 group-hover:text-primary dark:text-white leading-tight pr-4">
                    <a href="{{ url('/jobs/'.$job->slug) }}">{{ $job->title }}</a>
                </h2>
                <p class="text-xs font-medium text-slate-600 dark:text-slate-400 mt-1">
                    {{ $job->company_name ?? $job->category?->name }} • {{ $job->city?->name }}
                </p>
            </div>
        </div>
        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 whitespace-nowrap">Apply Soon</span>
    </div>
    
    <div class="flex flex-wrap gap-2 text-[10px] font-black uppercase text-slate-600">
        @if($job->job_type) <span class="bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700">{{ $job->job_type }}</span> @endif
        @if($job->experience) <span class="bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-primary">{{ $job->experience }}</span> @endif
        
        @if($job->has_accommodation) 
            <span class="bg-rose-100 dark:bg-rose-900/30 text-rose-700 dark:text-rose-400 px-2.5 py-1 rounded-lg border border-rose-200 dark:border-rose-900/50 flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px]">house</span>Hostel
            </span> 
        @endif

        @if($job->has_transport) 
            <span class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 px-2.5 py-1 rounded-lg border border-blue-200 dark:border-blue-900/50 flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px]">directions_bus</span>Transport
            </span> 
        @endif

        @if($job->is_remote)
            <span class="bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-2.5 py-1 rounded-lg border border-emerald-200 dark:border-emerald-900/50 flex items-center gap-1">
                <span class="material-symbols-outlined text-[12px]">home_work</span>Remote
            </span>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-xs text-slate-600 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800 pt-4">
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">payments</span>
            <span class="font-bold">{{ $job->salary_range ?: ($job->salary_min ? number_format($job->salary_min).' PKR' : 'Market Rate') }}</span>
        </div>
        <div class="flex items-center gap-1.5 ml-auto">
            <span class="material-symbols-outlined text-[16px]">schedule</span>
            <span class="font-bold">{{ $job->deadline ? \Carbon\Carbon::parse($job->deadline)->format('d M, Y') : 'Ongoing' }}</span>
        </div>
    </div>
</div>
