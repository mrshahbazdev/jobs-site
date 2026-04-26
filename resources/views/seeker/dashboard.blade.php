<x-layout>
    @section('title', 'My Dashboard - JobsPic')

    <main class="mx-auto max-w-7xl px-4 py-8 lg:px-10">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900">
                    Welcome back, {{ $user->name }}
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Manage your profile, applications, and saved jobs.
                </p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 transition hover:bg-slate-50">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">logout</span>
                    Log out
                </button>
            </form>
        </div>

        @if (! $user->hasVerifiedEmail())
            <div class="mb-6 rounded-lg bg-amber-50 p-4 text-sm text-amber-800 ring-1 ring-inset ring-amber-200">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-amber-600" aria-hidden="true">mark_email_unread</span>
                    <div class="flex-1">
                        <p class="font-semibold">Please verify your email address</p>
                        <p class="mt-1">We sent a verification link to <span class="font-semibold">{{ $user->email }}</span>. Can't find it? Resend below.</p>
                        <form method="POST" action="{{ route('verification.send') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="text-amber-700 underline hover:text-amber-900">
                                Resend verification email
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Profile completion</h2>
                    <span class="text-2xl font-black text-primary">{{ $user->profile_completion_percent }}%</span>
                </div>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                    <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $user->profile_completion_percent }}%"></div>
                </div>
                <p class="mt-3 text-xs text-slate-500">Verify email, add phone, upload CV to reach 100%.</p>
            </div>

            <a href="{{ url('/bookmarks') }}" class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 transition hover:ring-primary">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-primary/10 p-2 text-primary">
                        <span class="material-symbols-outlined text-2xl" aria-hidden="true">bookmark</span>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900">{{ $bookmarkCount }}</p>
                        <p class="text-sm text-slate-500">Saved jobs</p>
                    </div>
                </div>
                <p class="mt-4 text-xs text-slate-400 group-hover:text-primary">View saved jobs →</p>
            </a>

            <div class="rounded-xl bg-gradient-to-br from-slate-50 to-white p-6 shadow-sm ring-1 ring-slate-200">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-emerald-100 p-2 text-emerald-700">
                        <span class="material-symbols-outlined text-2xl" aria-hidden="true">description</span>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900">0</p>
                        <p class="text-sm text-slate-500">Applications</p>
                    </div>
                </div>
                <p class="mt-4 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                    <span class="material-symbols-outlined text-sm" aria-hidden="true">hourglass_top</span>
                    Coming soon
                </p>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="mb-3 text-lg font-semibold text-slate-900">Quick actions</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg bg-white p-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:ring-primary">
                    <span class="material-symbols-outlined text-primary" aria-hidden="true">person</span>
                    Edit profile
                </a>
                <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-lg bg-white p-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:ring-primary">
                    <span class="material-symbols-outlined text-primary" aria-hidden="true">search</span>
                    Browse jobs
                </a>
                <a href="{{ url('/bookmarks') }}" class="flex items-center gap-3 rounded-lg bg-white p-4 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:ring-primary">
                    <span class="material-symbols-outlined text-primary" aria-hidden="true">bookmark</span>
                    Saved jobs
                </a>
                <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-4 text-sm font-semibold text-slate-400 ring-1 ring-slate-200">
                    <span class="material-symbols-outlined" aria-hidden="true">upload_file</span>
                    Upload CV (soon)
                </div>
            </div>
        </div>
    </main>
</x-layout>
