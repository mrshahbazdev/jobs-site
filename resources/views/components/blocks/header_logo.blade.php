@php
    $url = (str_starts_with($block->url ?? '', 'http') || str_contains($block->url ?? '', '/')) ? $block->url : (Route::has($block->url ?? '') ? route($block->url) : url('/'));
@endphp
<a href="{{ $url }}" class="flex items-center gap-2 text-primary hover:opacity-80 transition-opacity">
    <span class="material-symbols-outlined text-3xl font-bold" aria-hidden="true" fetchpriority="high">{{ $block->icon ?? 'work' }}</span>
    <span class="text-2xl font-black leading-tight tracking-tight text-slate-900 dark:text-slate-100">
        @php
            $title = $block->title ?? 'JobsPic.com';
            // Split title to highlight "Pic" if it exists
            if (str_contains($title, 'Pic')) {
                [$first, $second] = explode('Pic', $title, 2);
                echo $first . '<span class="text-primary">Pic</span>' . $second;
            } else {
                echo $title;
            }
        @endphp
    </span>
</a>
