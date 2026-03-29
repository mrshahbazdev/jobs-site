<x-layout>
    <x-slot name="title">Browse All Job Categories & Lists - JobsPic.com</x-slot>

    <div class="bg-slate-50 dark:bg-slate-900/50 py-12">
        <div class="mx-auto max-w-7xl px-4 lg:px-10">
            <!-- Dynamic Block System with Smart Column Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 auto-rows-max">
                @foreach($allListsBlocks as $block)
                    {{-- Blocks like Headings and Hero Cards should span full width, while List Groups span 1 column --}}
                    @php
                        $isFullWidth = in_array($block->type, ['heading', 'hero_cards', 'newsletter', 'whatsapp_cta', 'category_grids', 'latest_jobs_list']);
                    @endphp
                    
                    <div class="{{ $isFullWidth ? 'col-span-full' : '' }}">
                        @include('components.blocks.' . $block->type, ['block' => $block])
                    </div>
                @endforeach
            </div>
            <!-- End Dynamic Block System -->

            @if($allListsBlocks->isEmpty())
                <div class="text-center py-20 px-4">
                    <h2 class="text-2xl font-black text-slate-400">No content blocks found for this page.</h2>
                    <p class="text-slate-500 mt-2">Add blocks in Site Builder with page "All Job Lists".</p>
                </div>
            @endif
        </div>
    </div>
</x-layout>
