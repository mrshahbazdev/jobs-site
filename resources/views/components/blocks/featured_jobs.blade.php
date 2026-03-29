<!-- Block: Featured Opportunities -->
@if($featuredJobs->count() > 0)
<section class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold flex items-center gap-2">
            <span class="material-symbols-outlined text-amber-500" aria-hidden="true">stars</span>
            Featured Opportunities
        </h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6">
        @foreach($featuredJobs->take($block->job_count ?? 4) as $job)
            <x-job-card :job="$job" />
        @endforeach
    </div>
</section>
@endif
