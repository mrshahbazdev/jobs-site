<x-layout>
    @section('title', $job->title . ' - Jobs in ' . $job->city->name . ' | JobsPic')
    @section('meta_description', $job->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($job->description_html), 150))
    @section('meta_keywords', $job->meta_keywords ?: 'jobs in pakistan, ' . $job->category->name . ', ' . $job->city->name)

    @push('extra_head')
        <link rel="amphtml" href="{{ route('jobs.amp', $job->slug) }}">
        
        <script type="application/ld+json">
        {
          "@@context": "https://schema.org",
          "@@type": "NewsArticle",
          "headline": "{{ $job->title }}",
          "datePublished": "{{ $job->created_at->toIso8601String() }}",
          "dateModified": "{{ $job->updated_at->toIso8601String() }}",
          "author": [{
              "@@type": "Organization",
              "name": "JobsPic",
              "url": "{{ url('/') }}"
            }],
          "image": [
            "{{ $job->company_logo ? asset('storage/'.$job->company_logo) : asset('icons/icon-512x512.png') }}"
          ]
        }
        </script>



        @if($job->schema_json)
            <script type="application/ld+json">
            {!! $job->schema_json !!}
            </script>
        @else
            <script type="application/ld+json">
            {!! $job->generateSchema() !!}
            </script>
        @endif
    @endpush

    <main class="mx-auto flex flex-col lg:flex-row w-full max-w-7xl grow gap-8 px-4 py-8 lg:px-10">
        <!-- Main Job Content -->
        <article class="w-full lg:w-2/3 flex flex-col bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            
            <!-- Job Header Image/Banner -->
            <div class="w-full h-56 md:h-80 bg-slate-100 dark:bg-slate-800 flex items-center justify-center border-b border-slate-200 dark:border-slate-800 relative overflow-hidden">
                @if($job->company_logo)
                    <img src="{{ asset('storage/'.$job->company_logo) }}" alt="{{ $job->title }} job vacancies at {{ $job->company_name ?? $job->department }} in {{ $job->city->name }} - Apply Now" class="w-full h-full object-cover">
                @else
                    <span class="material-symbols-outlined text-6xl text-slate-300 dark:text-slate-600">work</span>
                @endif
                
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                    <div class="bg-primary text-white font-bold px-3 py-1 rounded-full text-xs uppercase tracking-wide shadow-sm">
                        {{ $job->category->name }}
                    </div>
                    @if($job->is_featured)
                    <div class="bg-amber-500 text-white font-black px-3 py-1 rounded-full text-[10px] uppercase tracking-widest shadow-lg flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">star</span> FEATURED
                    </div>
                    @endif
                </div>
                
                <!-- Print Button -->
                <button onclick="window.print()" class="absolute top-4 right-4 bg-white/90 backdrop-blur text-slate-900 px-4 py-2 rounded-xl text-xs font-black shadow-xl hover:bg-primary hover:text-white transition-all flex items-center gap-2 no-print">
                    <span class="material-symbols-outlined text-sm">print</span> PRINT
                </button>
            </div>

            <div class="p-6 md:p-10">
                <!-- Breadcrumbs -->
                <nav class="flex text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium">
                    <a href="{{ url('/') }}" class="hover:text-primary">Home</a>
                    <span class="mx-2">&rsaquo;</span>
                    <a href="{{ url('/categories/'.$job->category->slug) }}" class="hover:text-primary">{{ $job->category->name }}</a>
                    <span class="mx-2">&rsaquo;</span>
                    <span class="text-slate-900 dark:text-slate-100 truncate max-w-[200px] sm:max-w-none">{{ $job->title }}</span>
                </nav>

                <div class="prose prose-slate prose-lg dark:prose-invert max-w-none 
                            prose-headings:font-black prose-a:text-primary prose-a:no-underline hover:prose-a:underline
                            prose-th:bg-primary/5 prose-th:p-4 prose-td:p-4 prose-table:overflow-hidden prose-table:rounded-xl prose-table:border prose-table:border-slate-200 dark:prose-table:border-slate-700
                            prose-li:marker:text-primary">
                    
                    <div class="text-3xl md:text-5xl font-black tracking-tight mb-2 text-slate-900 dark:text-white">{{ $job->title }}</div>
                    @if($job->company_name)
                        <div class="text-xl font-bold text-slate-600 dark:text-slate-400 mt-0 mb-4">{{ $job->company_name }}</div>
                    @endif
                    
                    <div class="flex flex-wrap gap-3 mb-8 mt-4">
                        <span class="bg-sky-100 text-sky-800 text-sm font-bold px-4 py-1.5 rounded-full border border-sky-200 shadow-sm">📍 {{ $job->city->name }}</span>
                        <span class="bg-emerald-100 text-emerald-800 text-sm font-bold px-4 py-1.5 rounded-full border border-emerald-200 shadow-sm">📅 Posted {{ $job->created_at->diffForHumans() }}</span>
                        @if($job->job_type)
                        <span class="bg-indigo-100 text-indigo-800 text-sm font-bold px-4 py-1.5 rounded-full border border-indigo-200 shadow-sm">💼 {{ $job->job_type }}</span>
                        @endif
                    </div>

                    @if($job->salary_range || $job->deadline || $job->department || $job->salary_min)
                    <div class="bg-amber-50 dark:bg-amber-900/20 p-5 rounded-2xl border-l-[6px] border-amber-500 mb-8 shadow-sm">
                        <h4 class="font-extrabold text-lg text-amber-900 dark:text-amber-400 mb-2 mt-0">🚀 Key Information</h4>
                        @if($job->department) <p class="text-amber-800 dark:text-amber-200 my-1"><strong>Department:</strong> {{ $job->department }}</p> @endif
                        @if($job->salary_range) 
                            <p class="text-amber-800 dark:text-amber-200 my-1"><strong>Salary:</strong> {{ $job->salary_range }}</p> 
                        @elseif($job->salary_min)
                            <p class="text-amber-800 dark:text-amber-200 my-1"><strong>Salary:</strong> {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }} PKR</p>
                        @endif
                        @if($job->deadline) <p class="text-amber-800 dark:text-amber-200 my-1"><strong>Deadline:</strong> {{ \Carbon\Carbon::parse($job->deadline)->format('M d, Y') }}</p> @endif
                    </div>
                    @endif

                    <style>
                        .job-description { font-family: 'Inter', 'Segoe UI', sans-serif; color: #334155; line-height: 1.9; font-size: 1.05rem; }
                        .job-description h1 { font-size: 1.9rem; font-weight: 800; color: #0f172a; margin: 2rem 0 1rem; border-bottom: 3px solid #e2e8f0; padding-bottom: 0.5rem; line-height: 1.3; }
                        .job-description h2 { font-size: 1.45rem; font-weight: 700; color: #1e3a5f; margin: 2rem 0 0.75rem; padding-left: 14px; border-left: 5px solid #0284c7; }
                        .job-description h3 { font-size: 1.2rem; font-weight: 700; color: #334155; margin: 1.5rem 0 0.5rem; }
                        .job-description p { margin-bottom: 1.25rem; text-align: justify; }
                        .job-description ul, .job-description ol { margin: 0.75rem 0 1.25rem 1.5rem; }
                        .job-description ul { list-style-type: disc; }
                        .job-description ol { list-style-type: decimal; }
                        .job-description li { margin-bottom: 0.5rem; }
                        .job-description strong { color: #0f172a; font-weight: 700; }
                        .job-description table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
                        .job-description th { background: #f0f9ff; color: #0369a1; font-weight: 800; padding: 12px 16px; text-align: left; border-bottom: 2px solid #bae6fd; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
                        .job-description td { padding: 11px 16px; border-bottom: 1px solid #f1f5f9; color: #475569; }
                        .job-description tr:hover td { background: #f8fafc; }
                        .job-description a { color: #0284c7; text-decoration: underline; }
                        .job-description blockquote { border-left: 4px solid #0284c7; background: #f0f9ff; padding: 1rem 1.25rem; border-radius: 0 12px 12px 0; margin: 1.5rem 0; font-style: italic; color: #0369a1; }
                        .job-description pre { background: #0f172a; color: #7dd3fc; padding: 1.25rem; border-radius: 12px; overflow-x: auto; font-size: 0.9rem; margin: 1.5rem 0; }
                        @media (max-width: 640px) { .job-description h1 { font-size: 1.5rem; } .job-description h2 { font-size: 1.2rem; } .job-description { font-size: 0.98rem; } }
                    </style>
                    <div class="job-description max-w-none">
                        {!! $job->description_html !!}
                    </div>
                    <div class="mt-12 mb-8 no-print pt-8 border-t border-slate-100 dark:border-slate-800">
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4">Share this job with friends</p>
                        <div class="flex flex-wrap gap-3">
                            <!-- WhatsApp Share -->
                            <a href="https://wa.me/?text={{ urlencode($job->title . ' - ' . url()->current()) }}" target="_blank" class="flex items-center gap-2 bg-[#25D366] text-white px-4 py-2 rounded-xl font-bold shadow-md hover:opacity-90 transition-all text-sm">
                                <span class="material-symbols-outlined text-sm">share</span> WhatsApp
                            </a>
                            <!-- Facebook -->
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="flex items-center gap-2 bg-[#1877F2] text-white px-4 py-2 rounded-xl font-bold shadow-md hover:opacity-90 transition-all text-sm">
                                <span class="material-symbols-outlined text-sm">share</span> Facebook
                            </a>
                            <!-- LinkedIn -->
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" class="flex items-center gap-2 bg-[#0A66C2] text-white px-4 py-2 rounded-xl font-bold shadow-md hover:opacity-90 transition-all text-sm">
                                <span class="material-symbols-outlined text-sm">share</span> LinkedIn
                            </a>
                            
                            <!-- Bookmark (Save Job) -->
                            <button id="bookmark-btn" 
                                    data-job-id="{{ $job->id }}"
                                    data-active="{{ $job->bookmarkedByUsers->contains(auth()->id()) ? 'true' : 'false' }}"
                                    onclick="toggleBookmark()"
                                    class="flex items-center gap-2 {{ $job->bookmarkedByUsers->contains(auth()->id()) ? 'bg-primary text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }} px-4 py-2 rounded-xl font-bold shadow-sm hover:opacity-90 transition-all text-sm ml-auto">
                                <span class="material-symbols-outlined text-sm" id="bookmark-icon">
                                    {{ $job->bookmarkedByUsers->contains(auth()->id()) ? 'bookmark_added' : 'bookmark' }}
                                </span> 
                                <span id="bookmark-text">
                                    {{ $job->bookmarkedByUsers->contains(auth()->id()) ? 'Saved' : 'Save Job' }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <script>
                        function toggleBookmark() {
                            @if(auth()->guest())
                                window.location.href = "{{ route('login') }}";
                                return;
                            @endif

                            const btn = document.getElementById('bookmark-btn');
                            const icon = document.getElementById('bookmark-icon');
                            const text = document.getElementById('bookmark-text');
                            const jobId = btn.getAttribute('data-job-id');
                            const isActive = btn.getAttribute('data-active') === 'true';

                            // Immediate UI feedback
                            if (isActive) {
                                btn.classList.remove('bg-primary', 'text-white');
                                btn.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
                                icon.innerText = 'bookmark';
                                text.innerText = 'Save Job';
                                btn.setAttribute('data-active', 'false');
                            } else {
                                btn.classList.add('bg-primary', 'text-white');
                                btn.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
                                icon.innerText = 'bookmark_added';
                                text.innerText = 'Saved';
                                btn.setAttribute('data-active', 'true');
                            }

                            fetch(`/bookmarks/${jobId}/toggle`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log(data.message);
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                // Revert UI if error
                                if (isActive) {
                                    btn.classList.add('bg-primary', 'text-white');
                                    btn.classList.remove('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
                                    icon.innerText = 'bookmark_added';
                                    text.innerText = 'Saved';
                                    btn.setAttribute('data-active', 'true');
                                } else {
                                    btn.classList.remove('bg-primary', 'text-white');
                                    btn.classList.add('bg-slate-100', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
                                    icon.innerText = 'bookmark';
                                    text.innerText = 'Save Job';
                                    btn.setAttribute('data-active', 'false');
                                }
                            });
                        }
                    </script>

                    @if($job->deadline && \Carbon\Carbon::parse($job->deadline)->isFuture())
                    <div class="text-center mt-4 mb-12 flex flex-col md:flex-row items-center justify-center gap-4 no-print">
                        @if($job->whatsapp_number)
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $job->whatsapp_number) }}?text={{ urlencode('I am interested in applying for the job: ' . $job->title) }}" target="_blank" class="inline-flex items-center gap-3 bg-[#25D366] hover:bg-[#20ba59] text-white font-black py-4 px-10 rounded-full shadow-xl transition-all transform hover:scale-105 active:scale-95 text-xl">
                           <span class="material-symbols-outlined">chat</span> Apply on WhatsApp
                        </a>
                        @endif
                    </div>
                    @else
                    <div class="text-red-700 dark:text-red-400 font-bold bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border border-red-200 dark:border-red-800 mt-12 mb-12 text-center no-print">
                        ⚠️ This job listing has expired or is no longer accepting applications.
                    </div>
                    @endif

                </div>
            </div>
        </article>

        <!-- Sidebar -->
        <aside class="w-full lg:w-1/3 flex flex-col gap-6 no-print">
            <div class="bg-gradient-to-br from-[#128C7E] to-[#25D366] rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="text-xl font-black mb-2">Join Our WhatsApp</h3>
                    <p class="text-white/90 text-sm mb-6 leading-relaxed">Stay updated with the latest job alerts in your city!</p>
                    <a href="https://chat.whatsapp.com/invite/YOUR_LINK" target="_blank" class="block w-full text-center bg-white text-[#128C7E] font-bold py-3 rounded-xl shadow-md hover:bg-slate-50 transition-colors">
                        Join Community
                    </a>
                </div>
            </div>

            {!! $settings['ad_job_sidebar'] ?? '' !!}

            <!-- Related Category Jobs -->
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 italic">More In {{ $job->category->name }}</h3>
                <div class="flex flex-col gap-4">
                    @forelse($relatedJobs as $rJob)
                    <a href="{{ url('/jobs/'.$rJob->slug) }}" class="group flex items-start gap-4 p-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-all border border-transparent hover:border-slate-100 dark:hover:border-slate-700">
                        <div class="w-12 h-12 bg-sky-50 dark:bg-sky-900/20 rounded-lg flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                            <span class="material-symbols-outlined text-xl">stat_0</span>
                        </div>
                        <div class="flex flex-col">
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200 line-clamp-2 leading-snug">{{ $rJob->title }}</h4>
                            <span class="text-[10px] text-slate-400 mt-1 font-medium">{{ $rJob->city->name }} • {{ $rJob->created_at->diffForHumans() }}</span>
                        </div>
                    </a>
                    @empty
                    <p class="text-sm text-slate-500">Check back soon for more job listings.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </main>

    <style>
        @@media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .mx-auto { max-width: 100% !important; margin: 0 !important; border: none !important; }
            article { border: none !important; shadow: none !important; }
        }
    </style>
</x-layout>
