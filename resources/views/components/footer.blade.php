<footer class="mt-auto border-t border-slate-200 bg-white py-12 dark:border-slate-800 dark:bg-background-dark">
    <div class="mx-auto max-w-7xl px-4 lg:px-10">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
            {{-- Column 1: Logo & Vision or Custom Content --}}
            <div class="flex flex-col gap-4">
                @php
                    $logoBlock = $headerBlocks->where('type', 'header_logo')->first();
                @endphp
                @if($logoBlock)
                    @include('components.blocks.header_logo', ['block' => $logoBlock])
                @else
                    <div class="flex items-center gap-2 text-primary">
                        <span class="material-symbols-outlined text-3xl font-bold" aria-hidden="true">work</span>
                        <h2 class="text-2xl font-black leading-tight tracking-tight text-slate-900 dark:text-slate-100">Jobs<span class="text-primary">Pic</span><span class="text-slate-400 text-lg">.com</span></h2>
                    </div>
                @endif
                <p class="text-sm text-slate-500">Connecting talent with opportunity across Pakistan. Join thousands of professionals finding their next career step with us.</p>
            </div>

            {{-- Column 2, 3, 4: Managed Columns or Default --}}
            @foreach($footerBlocks->where('type', 'footer_column') as $block)
                @include('components.blocks.footer_column', ['block' => $block])
            @endforeach

            @if($footerBlocks->where('type', 'footer_column')->isEmpty())
                <div>
                    <h4 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Quick Links</h4>
                    <ul class="flex flex-col gap-2 text-sm text-slate-500">
                        <li><a class="hover:text-primary" href="{{ url('/') }}">Home</a></li>
                        <li><a class="hover:text-primary" href="{{ url('/categories') }}">Categories</a></li>
                        <li><a class="hover:text-primary font-bold text-primary" href="{{ route('jobs.all_lists') }}">All Job Lists</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Legal</h4>
                    <ul class="flex flex-col gap-2 text-sm text-slate-500">
                        <li><a class="hover:text-primary" href="{{ url('/about') }}">About Us</a></li>
                        <li><a class="hover:text-primary" href="{{ url('/privacy-policy') }}">Privacy Policy</a></li>
                        <li><a class="hover:text-primary" href="{{ url('/terms') }}">Terms & Conditions</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Follow Us</h4>
                    <div class="flex gap-4">
                        <a class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-primary hover:text-white dark:bg-slate-800" href="#"><span class="material-symbols-outlined" aria-hidden="true">public</span></a>
                        <a class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600 hover:bg-primary hover:text-white dark:bg-slate-800" href="#"><span class="material-symbols-outlined" aria-hidden="true">alternate_email</span></a>
                    </div>
                </div>
            @endif
        </div>

        @php
            $copyrightBlock = $footerBlocks->where('type', 'footer_copyright')->first();
        @endphp

        @if($copyrightBlock)
            @include('components.blocks.footer_copyright', ['block' => $copyrightBlock])
        @else
            <div class="mt-12 border-t border-slate-100 pt-8 text-center text-sm text-slate-400 dark:border-slate-800">
                &copy; <span class="current-year"></span> JobsPic.com. All rights reserved.
            </div>
        @endif
    </div>
</footer>
