<x-guest-layout>
    <div class="mb-4 text-center">
        <h1 class="text-xl font-black text-slate-900">Verify your email</h1>
        <p class="mt-2 text-sm text-slate-500">
            {{ __('Thanks for signing up! Please click the link we just emailed to you to verify your email address. Didn\'t get it? We\'ll send another one.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mb-4" status="{{ __('A new verification link has been sent to the email address you provided during registration.') }}" />
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-600 hover:text-primary underline">
                {{ __('Log out') }}
            </button>
        </form>
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend verification email') }}
            </x-primary-button>
        </form>
    </div>
</x-guest-layout>
