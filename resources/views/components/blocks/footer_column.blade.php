<div>
    <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">
        {{ $block->title }}
    </h3>
    <ul class="flex flex-col gap-2 text-sm text-slate-500">
        @foreach($block->cards ?? [] as $card)
            @php
                $url = (str_starts_with($card['url'] ?? '', 'http') || str_contains($card['url'] ?? '', '/')) ? $card['url'] : (Route::has($card['url'] ?? '') ? route($card['url']) : $card['url']);
            @endphp
            <li>
                <a href="{{ $url }}" class="hover:text-primary transition-colors flex items-center gap-2">
                    @if($card['icon'] ?? null)
                        <span class="material-symbols-outlined text-xs">{{ $card['icon'] }}</span>
                    @endif
                    {{ $card['title'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
