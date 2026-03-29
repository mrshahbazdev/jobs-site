<x-layout>
    <x-slot name="title">Latest Jobs in Pakistan 2026 - Govt & Private Positions | JobsPic</x-slot>
    
    @push('breadcrumb_schema')
    @php
        $breadcrumbSchema = [
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => [
                [
                    "@type" => "ListItem",
                    "position" => 1,
                    "name" => "Home",
                    "item" => url('/')
                ]
            ]
        ];
    @endphp

    <script type="application/ld+json">
        {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

    <!-- Dynamic Block System -->
    @php
        $blocks = \App\Models\HomeBlock::active()->where('page_slug', 'home')->ordered()->get();
    @endphp

    @foreach($blocks as $block)
        <div class="{{ in_array($block->type, ['newsletter', 'whatsapp_cta', 'hero_cards']) ? '' : 'mx-auto max-w-7xl px-4 lg:px-10 py-8' }}">
            @include('components.blocks.' . $block->type, ['block' => $block])
        </div>
    @endforeach
    <!-- End Dynamic Block System -->
</x-layout>