<div wire:poll.1s="pollProgress" class="p-4 space-y-4">
    @if(!$isScraping && (!$progress || $progress['status'] === 'completed'))
        <div class="text-center">
            <h2 class="text-xl font-bold mb-4">Start Scraping Jobs</h2>
            <p class="mb-4 text-gray-600 italic">Select a source and start scraping the latest job listings.</p>

            <div class="mb-4">
                <select wire:model="source" class="px-4 py-2 border rounded-lg text-sm">
                    <option value="pakistan-jobs">PakistanJobsBank.com</option>
                    <option value="jobsalert">JobsAlert.pk</option>
                    <option value="jobz-pk">Jobz.pk</option>
                </select>
            </div>

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
            
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden mb-4">
                @php
                    $percent = $progress['total'] > 0 ? ($progress['current'] / $progress['total']) * 100 : 0;
                @endphp
                <div class="bg-green-600 h-full transition-all duration-500" style="width: {{ $percent }}%"></div>
            </div>

            @if(!empty($progress['latest_findings']))
                <div class="mt-4 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700">
                    <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Latest Discoveries:</h4>
                    <ul class="space-y-1">
                        @foreach($progress['latest_findings'] as $finding)
                            <li class="text-[11px] text-slate-700 dark:text-slate-300 flex items-center gap-2 animate-in fade-in slide-in-from-left-2">
                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                <span class="truncate">{{ $finding }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($progress['status'] === 'completed')
                <div class="mt-4 text-center">
                    <button onclick="window.location.reload()" class="w-full py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-bold">
                        Finish & Refresh Dashboard
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>
