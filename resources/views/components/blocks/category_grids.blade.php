<!-- Block: Category Grids -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-x-12 gap-y-16">
    @foreach($landingGroups->where('section_type', 'grid') as $group)
    <div>
        <h3 class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-primary mb-6 pb-2 border-b border-slate-100 dark:border-slate-800">
            @if($group->icon)
                <span class="material-symbols-outlined text-lg">{{ $group->icon }}</span>
            @endif
            {{ $group->name }}
            @if($group->sub_label)
                <span class="text-[10px] lowercase text-slate-600 font-bold ml-auto">{{ $group->sub_label }}</span>
            @endif
        </h3>
        <div class="grid grid-cols-2 gap-x-4 gap-y-3">
            @foreach($group->links as $link)
                <a href="{{ route($link->route_name, $link->route_param) }}" class="text-[12px] font-bold text-slate-600 dark:text-slate-400 hover:text-primary transition-colors flex items-center gap-1">
                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                    {{ $link->label }}
                </a>
            @endforeach
        </div>
    </div>
    @endforeach
</div>

@foreach($landingGroups->where('section_type', 'strip') as $group)
<!-- Quick Strip: {{ $group->name }} -->
<div class="mt-16 pt-8 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-center gap-8">
    @foreach($group->links as $link)
    <a href="{{ route($link->route_name, $link->route_param) }}" class="flex items-center gap-2 {{ $loop->first ? 'bg-red-50 dark:bg-red-950/30 px-4 py-2 rounded-full' : 'text-xs font-black text-slate-600 hover:text-primary uppercase tracking-widest' }}">
        @if($loop->first && $group->name === 'QuickLink')
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-600"></span>
            </span>
            <span class="text-xs font-black text-red-600 uppercase tracking-widest hover:underline">{{ $link->label }}</span>
        @else
            @if($link->icon)
                <span class="material-symbols-outlined text-lg">{{ $link->icon }}</span>
            @endif
            {{ $link->label }}
        @endif
    </a>
    @endforeach
</div>
@endforeach

@foreach($landingGroups->where('section_type', 'industry') as $group)
<!-- Industry Hub: {{ $group->name }} -->
<div class="mt-16 grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
    @foreach($group->links as $link)
        <a href="{{ route($link->route_name, $link->route_param) }}" class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 text-center group">
            <span class="text-[10px] font-black text-slate-600 dark:text-slate-500 uppercase tracking-widest block mb-1">Industry</span>
            <span class="text-xs font-bold text-slate-700 dark:text-slate-300 group-hover:text-primary transition-colors">{{ $link->label }}</span>
        </a>
    @endforeach
</div>
@endforeach

<div class="mt-8 flex justify-center">
    <a href="{{ route('jobs.all_lists') }}" class="flex items-center gap-2 text-xs font-black text-primary hover:underline uppercase tracking-widest bg-primary/5 px-6 py-3 rounded-full">
        <span class="material-symbols-outlined text-lg">apps</span>
        Browse all 200+ specialized lists
    </a>
</div>
