<x-layout>
    <main class="mx-auto flex w-full max-w-md flex-col items-center px-4 py-12 lg:py-16">
        <a href="{{ url('/') }}" class="mb-6 flex items-center gap-2 text-primary hover:opacity-80 transition-opacity">
            <span class="material-symbols-outlined text-3xl font-bold" aria-hidden="true">work</span>
            <span class="text-2xl font-black leading-tight tracking-tight text-slate-900">Jobs<span class="text-primary">Pic</span><span class="text-slate-400 text-lg">.com</span></span>
        </a>

        <div class="w-full rounded-xl bg-white p-6 shadow ring-1 ring-slate-200 sm:p-8">
            {{ $slot }}
        </div>
    </main>
</x-layout>
