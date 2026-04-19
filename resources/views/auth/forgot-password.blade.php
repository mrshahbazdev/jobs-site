<x-guest-layout>
    <div class="mb-4">
        <h1 class="text-xl font-black text-slate-900">Forgot your password?</h1>
        <p class="mt-2 text-sm text-slate-500">
            No problem. Enter your email and we'll send you a password reset link.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-sm text-slate-600 hover:text-primary underline">
                {{ __('Back to login') }}
            </a>
            <x-primary-button>
                {{ __('Email reset link') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
