<!-- Block: Hero Features -->
<div class="mx-auto max-w-7xl px-4 lg:px-10 mb-12 grid grid-cols-1 md:grid-cols-3 gap-6">
    @php
        $defaultCards = [
            [
                'label' => 'Direct Entry',
                'title' => 'Walk-in Interviews',
                'sub_title' => 'No test required, direct interviews.',
                'icon' => 'hail',
                'url' => route('jobs.walkin'),
                'color' => 'orange'
            ],
            [
                'label' => 'Easiest Way',
                'title' => 'WhatsApp Apply',
                'sub_title' => 'Apply directly via WhatsApp number.',
                'icon' => 'chat',
                'url' => route('jobs.whatsapp'),
                'color' => 'green'
            ],
            [
                'label' => 'Work from Home',
                'title' => 'Remote Jobs',
                'sub_title' => 'International & local remote work.',
                'icon' => 'distance',
                'url' => route('jobs.remote'),
                'color' => 'blue'
            ]
        ];
        
        $cards = !empty($block->cards) ? $block->cards : $defaultCards;
        $colors = ['orange', 'green', 'blue', 'purple', 'rose', 'amber'];
    @endphp

    @foreach($cards as $index => $card)
        @php 
            $color = $card['color'] ?? $colors[$index % count($colors)]; 
            $url = (str_starts_with($card['url'] ?? '', 'http') || str_contains($card['url'] ?? '', '/')) ? $card['url'] : (Route::has($card['url'] ?? '') ? route($card['url']) : '#');
        @endphp
        <a href="{{ $url }}" class="group relative overflow-hidden flex items-center justify-between p-6 bg-gradient-to-br from-{{ $color }}-50 to-{{ $color }}-100 dark:from-{{ $color }}-900/20 dark:to-{{ $color }}-800/20 rounded-2xl border border-{{ $color }}-200 dark:border-{{ $color }}-800/50 hover:shadow-lg transition-all">
            <div class="relative z-10">
                <span class="text-[10px] font-black uppercase tracking-widest text-{{ $color }}-700 dark:text-{{ $color }}-400">{{ $card['label'] ?? 'Feature' }}</span>
                <h3 class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $card['title'] }}</h3>
                <p class="text-xs text-{{ $color }}-900 dark:text-{{ $color }}-200 mt-1">{{ $card['sub_title'] ?? '' }}</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-{{ $color }}-200 dark:text-{{ $color }}-800/30 group-hover:scale-110 transition-transform">{{ $card['icon'] ?? 'star' }}</span>
        </a>
    @endforeach
</div>
