@php
    $url = (str_starts_with($block->url ?? '', 'http') || str_contains($block->url ?? '', '/')) ? $block->url : (Route::has($block->url ?? '') ? route($block->url) : $block->url);
    $isButton = str_contains($block->title, 'Button') || $block->icon === 'upload_file' || $block->icon === 'chat';
@endphp

@if($isButton)
    <a href="{{ $url }}" class="hidden md:inline-flex items-center justify-center rounded-lg {{ $block->icon === 'chat' ? 'bg-[#075E54] text-white border-[#064e46]' : 'bg-primary/10 text-primary border-primary/20' }} px-4 py-2.5 text-sm font-black hover:opacity-80 transition-colors border">
        @if($block->icon)
            @if($block->icon === 'chat')
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 000 12a12 12 0 001.928 6.551l-1.928 5.449 5.629-1.896A11.93 11.93 0 0011.944 24C18.57 24 24 18.608 24 12c0-6.608-5.43-12-12.056-12zM12 21.841c-1.583 0-3.13-.414-4.5-1.206l-3.21.108.857-3.107c-.902-1.428-1.382-3.08-1.382-4.792C3.765 6.47 8.356 2.062 14.887 2.062 21.417 2.062 26 6.47 26 12.846 26 19.222 21.417 21.841 12 21.841zm6.756-9.157c-.37-.184-2.185-1.077-2.525-1.201-.341-.124-.588-.184-.836.184s-.95 1.201-1.166 1.448c-.216.248-.433.277-.803.093-2.193-1.085-3.815-2.613-5.06-4.757-.221-.383.22-.361.579-1.072.124-.247.062-.464-.031-.649-.093-.184-.835-2.01-1.144-2.753-.3-.721-.606-.622-.835-.634h-.711c-.247 0-.649.092-.99.463s-1.3 1.267-1.3 3.09 1.33 3.585 1.516 3.832c2.094 2.766 5.093 4.542 8.349 5.4 1.246.328 2.247.412 3.064.331.956-.094 2.185-.896 2.494-1.761.309-.865.309-1.606.216-1.761-.092-.153-.34-.246-.71-.431z"/></svg>
            @else
                <span class="material-symbols-outlined mr-2">{{ $block->icon }}</span>
            @endif
        @endif
        {{ $block->title }}
    </a>
@else
    <a href="{{ $url }}" class="hidden md:inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-100 transition-colors">
        @if($block->icon)
            <span class="material-symbols-outlined mr-2">{{ $block->icon }}</span>
        @endif
        {{ $block->title }}
    </a>
@endif
