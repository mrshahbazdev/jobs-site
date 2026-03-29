<div wire:poll.1s="pollProgress" class="p-4 space-y-4">
    @if(!$isScraping && (!$progress || $progress['status'] === 'completed'))
        <div class="text-center">
            <h2 class="text-xl font-bold mb-4">Start Scraping Pakistan Jobs</h2>
            <p class="mb-4 text-gray-600 italic">This will update the latest job listings and advertisements.</p>
            <button wire:click="startScraping" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                🚀 Start Scraper
            </button>
        </div>
    @elseif($progress)
        <div class="space-y-2">
            <div class="flex justify-between text-sm">
                <span>
                    @if($progress['status'] === 'starting')
                        Initializing...
                    @elseif($progress['status'] === 'running')
                        Scraping in progress...
                    @elseif($progress['status'] === 'completed')
                        ✅ Scraping Completed!
                    @elseif($progress['status'] === 'error')
                        ❌ Error: {{ $progress['message'] }}
                    @endif
                </span>
                <span>{{ $progress['current'] }} / {{ $progress['total'] }}</span>
            </div>
            
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                @php
                    $percent = $progress['total'] > 0 ? ($progress['current'] / $progress['total']) * 100 : 0;
                @endphp
                <div class="bg-green-600 h-full transition-all duration-500" style="width: {{ $percent }}%"></div>
            </div>

            @if($progress['status'] === 'completed')
                <div class="mt-4 text-center">
                    <button onclick="window.location.reload()" class="text-sm text-blue-600 underline">Refresh Dashboard</button>
                </div>
            @endif
        </div>
    @endif
</div>
