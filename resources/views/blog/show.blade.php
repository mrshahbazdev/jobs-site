<x-layout>
    <article class="mx-auto max-w-4xl px-4 py-12 lg:px-10">
        <div class="mb-12 text-center">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-xs font-black uppercase tracking-widest text-primary">
                {{ $post->created_at->format('M d, Y') }}
            </div>
            <h1 class="text-4xl font-black text-slate-900 dark:text-white md:text-6xl tracking-tight leading-tight">{{ $post->title }}</h1>
        </div>

        @if($post->image)
            <div class="mb-12 overflow-hidden rounded-[2.5rem] shadow-2xl">
                <img src="{{ asset('storage/'.$post->image) }}" alt="{{ $post->title }}" class="w-full h-auto">
            </div>
        @endif

        <div class="prose prose-slate prose-lg dark:prose-invert max-w-none 
                    prose-headings:font-black prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                    prose-th:bg-primary/5 prose-th:p-4 prose-td:p-4 prose-table:overflow-hidden prose-table:rounded-xl prose-table:border prose-table:border-slate-200 dark:prose-table:border-slate-700
                    prose-li:marker:text-primary leading-relaxed text-slate-700 dark:text-slate-300">
            {!! $post->content !!}
        </div>

        <div class="mt-20 border-t border-slate-200 dark:border-slate-800 pt-10">
            <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
                <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 text-sm font-black text-slate-500 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-base">arrow_back</span>
                    Back to Articles
                </a>
                <div class="flex items-center gap-4">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Share Article</p>
                    <div class="flex gap-2">
                         <a href="https://wa.me/?text={{ urlencode($post->title . ' - ' . url()->current()) }}" target="_blank" class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#25D366] text-white shadow-md hover:opacity-90 transition-all">
                             <span class="material-symbols-outlined text-lg">share</span>
                         </a>
                         <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1877F2] text-white shadow-md hover:opacity-90 transition-all">
                             <span class="material-symbols-outlined text-lg">share</span>
                         </a>
                    </div>
                </div>
            </div>
        </div>
    </article>
</x-layout>
