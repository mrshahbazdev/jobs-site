<!-- Block: Newsletter & Subscription -->
<section class="bg-primary/5 py-16 px-4 lg:px-10">
    <div class="mx-auto max-w-7xl">
        <div class="rounded-3xl bg-primary p-8 md:p-16 text-center text-white relative overflow-hidden">
            <div class="absolute top-0 right-0 -mr-20 -mt-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
            <div class="relative z-10 flex flex-col items-center gap-6">
                <h2 class="text-3xl font-black md:text-5xl">{{ $block->heading_text ?? 'Never Miss an Opportunity' }}</h2>
                <p class="max-w-2xl text-lg text-white/90">{{ $block->sub_text ?? 'Subscribe to our daily newsletter or join our WhatsApp community to get the latest job alerts delivered directly to you.' }}</p>
                
                @if(session('success'))
                    <div class="bg-emerald-500/20 border border-emerald-500 text-white rounded-xl px-6 py-3 font-bold">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('subscribe') }}" method="POST" class="flex w-full max-w-md flex-col gap-3 sm:flex-row">
                    @csrf
                    <input name="email_or_phone" required class="flex-1 rounded-xl border-0 px-4 py-4 text-slate-900 placeholder:text-slate-700 focus:ring-2 focus:ring-white" placeholder="Email or WhatsApp number" type="text"/>
                    <button type="submit" class="rounded-xl bg-white px-8 py-4 font-black text-primary hover:bg-slate-100 transition-colors">Subscribe</button>
                </form>
                
                <div class="flex items-center gap-4">
                    <span class="h-px w-8 bg-white/30"></span>
                    <span class="text-sm font-bold uppercase tracking-wider text-white/80">OR</span>
                    <span class="h-px w-8 bg-white/30"></span>
                </div>

                <a href="https://chat.whatsapp.com/your-group-link" class="flex items-center gap-3 rounded-xl bg-[#075E54] px-8 py-4 font-black text-white hover:bg-[#064e46] transition-colors shadow-lg">
                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 000 12a12 12 0 001.928 6.551l-1.928 5.449 5.629-1.896A11.93 11.93 0 0011.944 24C18.57 24 24 18.608 24 12c0-6.608-5.43-12-12.056-12zM12 21.841c-1.583 0-3.13-.414-4.5-1.206l-3.21.108.857-3.107c-.902-1.428-1.382-3.08-1.382-4.792C3.765 6.47 8.356 2.062 14.887 2.062 21.417 2.062 26 6.47 26 12.846 26 19.222 21.417 21.841 12 21.841zm6.756-9.157c-.37-.184-2.185-1.077-2.525-1.201-.341-.124-.588-.184-.836.184s-.95 1.201-1.166 1.448c-.216.248-.433.277-.803.093-2.193-1.085-3.815-2.613-5.06-4.757-.221-.383.22-.361.579-1.072.124-.247.062-.464-.031-.649-.093-.184-.835-2.01-1.144-2.753-.3-.721-.606-.622-.835-.634h-.711c-.247 0-.649.092-.99.463s-1.3 1.267-1.3 3.09 1.33 3.585 1.516 3.832c2.094 2.766 5.093 4.542 8.349 5.4 1.246.328 2.247.412 3.064.331.956-.094 2.185-.896 2.494-1.761.309-.865.309-1.606.216-1.761-.092-.153-.34-.246-.71-.431z"/></svg>
                    Join WhatsApp Channel
                </a>
            </div>
        </div>
    </div>
</section>
