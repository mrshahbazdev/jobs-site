<x-layout>
    <x-slot name="title">Career Advice & Job Tips - JobsPic Blog</x-slot>

    <main class="mx-auto max-w-7xl px-4 py-12 lg:px-10">
        <div class="mb-12 text-center">
            <h1 class="text-4xl font-black text-slate-900 dark:text-white md:text-6xl tracking-tight">Career <span class="text-primary">Advice</span></h1>
            <p class="mt-4 text-lg text-slate-600 dark:text-slate-400">Expert tips to help you land your dream job in Pakistan.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($posts as $post)
                <article class="group relative flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white dark:bg-slate-900 dark:border-slate-800 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1">
                    <div class="aspect-[16/9] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                        @if($post->image)
                            <img src="{{ asset('storage/'.$post->image) }}" alt="{{ $post->title }}" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <span class="material-symbols-outlined text-6xl text-slate-300">import_contacts</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <div class="mb-3 flex items-center gap-3 text-xs font-bold uppercase tracking-widest text-primary">
                            <span>{{ $post->created_at->format('M d, Y') }}</span>
                        </div>
                        <h2 class="mb-4 text-xl font-black text-slate-900 dark:text-white group-hover:text-primary transition-colors">
                            <a href="{{ route('blog.show', $post->slug) }}">
                                <span class="absolute inset-0"></span>
                                {{ $post->title }}
                            </a>
                        </h2>
                        <p class="mb-6 line-clamp-3 text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                            {{ Str::limit(strip_tags($post->content), 120) }}
                        </p>
                        <div class="mt-auto flex items-center text-sm font-black text-primary">
                            Read More
                            <span class="material-symbols-outlined ml-1 text-base transition-transform group-hover:translate-x-1">arrow_forward</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-20 text-center">
                    <span class="material-symbols-outlined text-6xl text-slate-300 mb-4">edit_note</span>
                    <p class="text-slate-500">New articles are coming soon. Stay tuned!</p>
                </div>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    </main>
</x-layout>
