<x-layout>
    @section('title', 'My CVs - JobsPic')

    <main class="mx-auto max-w-5xl px-4 py-8 lg:px-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_back</span>
                    Back to dashboard
                </a>
                <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900">My CVs</h1>
                <p class="mt-1 text-sm text-slate-500">Build, customise and share professional CVs with AI assistance.</p>
            </div>
            <form method="POST" action="{{ route('cv.create') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">add</span>
                    New CV
                </button>
            </form>
        </div>

        @if (session('status') === 'cv-deleted')
            <div class="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                CV deleted.
            </div>
        @endif

        @if ($cvs->isEmpty())
            <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10 text-primary">
                    <span class="material-symbols-outlined text-3xl" aria-hidden="true">description</span>
                </div>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">No CV yet</h2>
                <p class="mt-1 text-sm text-slate-500">Create your first CV in minutes. Our AI will help polish your summary, generate bullet points, and suggest skills.</p>
                <form method="POST" action="{{ route('cv.create') }}" class="mt-5 inline-block">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90">
                        <span class="material-symbols-outlined text-lg" aria-hidden="true">auto_awesome</span>
                        Start building
                    </button>
                </form>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cvs as $cv)
                    <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200 transition hover:ring-primary">
                        <div class="relative h-40 bg-gradient-to-br" style="background: linear-gradient(135deg, {{ $cv->theme_color }}22, {{ $cv->theme_color }}05);">
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="material-symbols-outlined text-6xl" style="color: {{ $cv->theme_color }}" aria-hidden="true">description</span>
                            </div>
                            <span class="absolute left-3 top-3 rounded-full bg-white/90 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 ring-1 ring-slate-200">
                                {{ $cv->template }}
                            </span>
                            @if ($cv->is_public)
                                <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                                    Public
                                </span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-4">
                            <h3 class="truncate text-base font-bold text-slate-900">{{ $cv->title }}</h3>
                            <p class="mt-1 text-xs text-slate-500">Updated {{ $cv->updated_at->diffForHumans() }}</p>
                            @if ($cv->is_public && $cv->views_count > 0)
                                <p class="mt-1 inline-flex items-center gap-1 text-xs text-slate-500">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">visibility</span>
                                    {{ $cv->views_count }} {{ Str::plural('view', $cv->views_count) }}
                                </p>
                            @endif

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <a href="{{ route('cv.edit', $cv) }}" class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary/90">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">edit</span>
                                    Edit
                                </a>
                                <a href="{{ route('cv.download', $cv) }}" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                    <span class="material-symbols-outlined text-base" aria-hidden="true">download</span>
                                    PDF
                                </a>
                                <form method="POST" action="{{ route('cv.duplicate', $cv) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">content_copy</span>
                                        Duplicate
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('cv.destroy', $cv) }}" onsubmit="return confirm('Delete this CV permanently?');" class="ml-auto">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 transition hover:bg-rose-100">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </main>
</x-layout>
