<x-layout>
    <x-slot name="title">Contact Us - JobsPic</x-slot>

    <main class="mx-auto w-full max-w-4xl grow px-4 py-16 lg:px-10">
        <div class="grid gap-10 md:grid-cols-2">
            <div>
                <h1 class="text-4xl font-black tracking-tight">Contact Us</h1>
                <p class="mt-4 text-lg text-slate-600">
                    Have a question about a job listing, a correction, or feedback for JobsPic?
                    Send us a message and our team will get back to you.
                </p>
                <ul class="mt-8 flex flex-col gap-4 text-slate-600">
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">mail</span>
                        <span>Use the form and we'll reply at your email address.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">schedule</span>
                        <span>We usually respond within 1–2 business days.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-primary" aria-hidden="true">verified</span>
                        <span>Report incorrect or expired job listings — we review every report.</span>
                    </li>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @if (session('contact_success'))
                    <div class="rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                        {{ session('contact_success') }}
                    </div>
                @else
                    <form method="POST" action="{{ route('pages.contact.submit') }}" class="flex flex-col gap-4">
                        @csrf

                        <div>
                            <label for="name" class="mb-1 block text-sm font-bold text-slate-700">Your Name</label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                                class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                            @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="mb-1 block text-sm font-bold text-slate-700">Email Address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                                class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                            @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="subject" class="mb-1 block text-sm font-bold text-slate-700">Subject</label>
                            <input id="subject" name="subject" type="text" value="{{ old('subject') }}" required
                                class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">
                            @error('subject')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="message" class="mb-1 block text-sm font-bold text-slate-700">Message</label>
                            <textarea id="message" name="message" rows="5" required
                                class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary">{{ old('message') }}</textarea>
                            @error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit"
                            class="rounded-lg bg-primary px-6 py-3 text-sm font-bold text-white transition hover:bg-primary/90">
                            Send Message
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </main>
</x-layout>
