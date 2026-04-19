<x-layout>
    @section('title', 'My Profile - JobsPic')

    <main class="mx-auto max-w-3xl px-4 py-8 lg:px-10">
        <div class="mb-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-primary">
                <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_back</span>
                Back to dashboard
            </a>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-900">Profile settings</h1>
        </div>

        @if (session('status') === 'profile-updated')
            <div class="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                Profile updated.
            </div>
        @endif
        @if (session('status') === 'password-updated')
            <div class="mb-4 rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                Password updated.
            </div>
        @endif

        <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Basic info</h2>
            <p class="mt-1 text-sm text-slate-500">Update your name, email and phone number.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-6 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" :value="__('Full name')" />
                    <x-text-input id="name" class="mt-1" type="text" name="name" :value="old('name', $user->name)" required />
                    <x-input-error :messages="$errors->get('name')" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email', $user->email)" required />
                    <x-input-error :messages="$errors->get('email')" />
                    @if (! $user->hasVerifiedEmail())
                        <p class="mt-1 text-xs text-amber-700">Email not verified.</p>
                    @endif
                </div>

                <div>
                    <x-input-label for="phone" :value="__('Mobile number')" />
                    <x-text-input id="phone" class="mt-1" type="tel" name="phone" :value="old('phone', $user->phone)" placeholder="+923XXXXXXXXX" />
                    <x-input-error :messages="$errors->get('phone')" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Save changes</x-primary-button>
                </div>
            </form>
        </section>

        <section class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold text-slate-900">Change password</h2>

            <form method="POST" action="{{ route('profile.password') }}" class="mt-6 space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="current_password" :value="__('Current password')" />
                    <x-text-input id="current_password" class="mt-1" type="password" name="current_password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->updatePassword->get('current_password')" />
                </div>

                <div>
                    <x-input-label for="password" :value="__('New password')" />
                    <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->updatePassword->get('password')" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
                    <x-text-input id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Update password</x-primary-button>
                </div>
            </form>
        </section>

        <section class="mt-6 rounded-xl bg-rose-50 p-6 ring-1 ring-rose-200">
            <h2 class="text-lg font-semibold text-rose-900">Delete account</h2>
            <p class="mt-1 text-sm text-rose-700">This permanently deletes your account, bookmarks, and push subscriptions.</p>

            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-4 space-y-3" onsubmit="return confirm('Are you sure you want to permanently delete your account?');">
                @csrf
                @method('DELETE')
                <div>
                    <x-input-label for="delete_password" :value="__('Enter password to confirm')" />
                    <x-text-input id="delete_password" class="mt-1" type="password" name="password" required />
                    <x-input-error :messages="$errors->deleteAccount->get('password')" />
                </div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-500">
                    Delete my account
                </button>
            </form>
        </section>
    </main>
</x-layout>
