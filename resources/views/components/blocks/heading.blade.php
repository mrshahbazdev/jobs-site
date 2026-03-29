<!-- Block: Heading / Banner -->
<div class="mb-10 text-center">
    <h2 class="text-2xl font-black text-slate-900 dark:text-white uppercase tracking-tighter">{{ $block->heading_text ?? $block->title }}</h2>
    @if($block->sub_text ?? null)
        <p class="text-slate-500 text-sm mt-1">{{ $block->sub_text }}</p>
    @endif
</div>
