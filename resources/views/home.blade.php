<x-layout>
    <x-slot name="title">Latest Jobs in Pakistan 2026 - Govt & Private Positions | JobsPic</x-slot>
    
    @push('breadcrumb_schema')
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [{
        "@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ url('/') }}"
      }]
    }
    </script>
    @endpush

    <!-- Dynamic Block System -->
    @foreach($homeBlocks as $block)
        @php
            $isFullWidth = in_array($block->type, ['newsletter', 'whatsapp_cta', 'hero_cards']);
        @endphp
        
        @if($isFullWidth)
            <div class="{{ $block->type === 'newsletter' ? '' : 'py-8' }}">
                @include('components.blocks.' . $block->type, ['block' => $block])
            </div>
        @else
            <div class="mx-auto max-w-7xl px-4 lg:px-10 py-8">
                @include('components.blocks.' . $block->type, ['block' => $block])
            </div>
        @endif
    @endforeach
    <!-- End Dynamic Block System -->
</x-layout>
