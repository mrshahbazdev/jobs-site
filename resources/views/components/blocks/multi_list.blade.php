@php
    $data = [
        'provinces' => ['Punjab', 'Sindh', 'KPK', 'Balochistan', 'AJK', 'GB', 'ICT'],
        'education' => ['Matric', 'Intermediate', 'Bachelor', 'Master', 'PhD', 'DAE', 'Nursing'],
        'testing' => ['NTS', 'PPSC', 'FPSC', 'SPSC', 'BPSC', 'KPPSC', 'AJKPSC', 'OTS', 'PTS', 'UTS'],
        'roles' => ['Computer Operator', 'Driver', 'Security Guard', 'Clerk', 'Assistant', 'Accountant', 'Teacher'],
        'overseas' => ['Saudi Arabia', 'UAE', 'Qatar', 'Oman', 'Kuwait', 'USA', 'UK', 'Canada'],
        'councils' => ['PEC', 'PMC', 'Nursing', 'Law', 'Pharmacy'],
        'sectors' => ['Government', 'Private', 'Semi-Government', 'NGO'],
        'industries' => ['Banking & Finance', 'Telecommunications', 'Pharmaceutical', 'Textile', 'FMCG', 'Real Estate', 'Automotive', 'IT & Software'],
        'contracts' => ['Permanent', 'Contract Basis', 'Ad-hoc', 'Daily Wages'],
        'skills' => ['MS Office', 'Typing', 'Graphics Design', 'Web Development', 'SEO', 'Digital Marketing', 'ACCA/CA'],
    ];

    $source = $block->list_source;
    $items = [];
    $routeName = 'jobs.' . $source;

    // Special cases
    if ($source === 'categories') {
        $items = \App\Models\Category::all()->map(fn($c) => ['label' => $c->name, 'url' => route('categories.show', $c)]);
    } elseif ($source === 'cities') {
        $items = \App\Models\City::all()->take(12)->map(fn($c) => ['label' => $c->name, 'url' => route('cities.show', $c)]);
    } elseif ($source === 'archives') {
        $items = collect([
            ['label' => 'March 2026', 'url' => route('jobs.archive', ['year' => 2026, 'month' => 3])],
            ['label' => 'February 2026', 'url' => route('jobs.archive', ['year' => 2026, 'month' => 2])],
        ]);
    } elseif (isset($data[$source])) {
        // Find the matching route name for standard lists
        $routeMap = [
            'provinces' => 'jobs.province',
            'education' => 'jobs.education',
            'testing' => 'jobs.testing_service',
            'roles' => 'jobs.role',
            'overseas' => 'jobs.country',
            'councils' => 'jobs.council',
            'sectors' => 'jobs.sector',
            'industries' => 'jobs.industrial',
            'contracts' => 'jobs.contract',
            'skills' => 'jobs.skill',
        ];
        $targetRoute = $routeMap[$source] ?? 'jobs.all_lists';
        
        $items = collect($data[$source])->map(fn($i) => [
            'label' => $i,
            'url' => route($targetRoute, $i)
        ]);
    }

    $displayType = $block->display_type ?? 'list';
@endphp

<div class="bg-white dark:bg-slate-800 p-6 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 h-full">
    <h2 class="flex items-center gap-2 text-primary font-black uppercase tracking-widest text-sm mb-6">
        <span class="material-symbols-outlined">{{ $block->icon ?? 'list_alt' }}</span>
        {{ $block->heading_text ?? $block->title }}
    </h2>

    @if($displayType === 'grid')
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($items as $item)
                <a href="{{ $item['url'] }}" class="px-4 py-2 bg-slate-50 dark:bg-slate-900 rounded-lg text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-primary hover:text-white transition-all text-center">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    @else
        <ul class="space-y-3">
            @foreach($items as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="text-sm font-bold text-slate-600 dark:text-slate-400 hover:text-primary transition-colors flex justify-between items-center group">
                        {{ $item['label'] }} <span class="material-symbols-outlined text-xs group-hover:translate-x-1 transition-transform">arrow_forward</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
