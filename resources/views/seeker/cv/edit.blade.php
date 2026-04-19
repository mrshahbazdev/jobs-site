<x-layout>
    @section('title', 'CV Builder - JobsPic')

    @php
        $publicUrl = route('cv.public', $cv->share_uuid);
    @endphp

    <main
        x-data="cvBuilder()"
        x-init="init()"
        class="mx-auto max-w-[1400px] px-3 py-4 lg:px-6"
    >
        {{-- Toolbar --}}
        <div class="mb-4 flex flex-col gap-3 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('cv.index') }}" class="inline-flex items-center gap-1 rounded-lg p-1.5 text-sm text-slate-500 hover:bg-slate-100">
                    <span class="material-symbols-outlined text-lg" aria-hidden="true">arrow_back</span>
                </a>
                <input
                    type="text"
                    x-model="cv.title"
                    @input.debounce.500ms="autoSave()"
                    placeholder="CV title"
                    class="w-64 rounded-lg border border-transparent bg-slate-50 px-3 py-1.5 text-base font-bold text-slate-900 focus:border-primary focus:bg-white focus:outline-none"
                />
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <template x-if="saveState === 'saving'">
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-3 w-3 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="60" stroke-linecap="round"/></svg>
                            Saving…
                        </span>
                    </template>
                    <template x-if="saveState === 'saved'">
                        <span class="inline-flex items-center gap-1 text-emerald-600">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">check_circle</span>
                            Saved
                        </span>
                    </template>
                    <template x-if="saveState === 'error'">
                        <span class="inline-flex items-center gap-1 text-rose-600">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">error</span>
                            Save failed
                        </span>
                    </template>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                {{-- Template picker --}}
                <div class="flex items-center rounded-lg bg-slate-100 p-0.5 text-xs">
                    <template x-for="t in ['modern','classic','minimal']" :key="t">
                        <button
                            type="button"
                            @click="cv.template = t; autoSave()"
                            :class="cv.template === t ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'"
                            class="rounded-md px-3 py-1 font-semibold capitalize transition"
                            x-text="t"
                        ></button>
                    </template>
                </div>

                {{-- Color picker --}}
                <label class="inline-flex items-center gap-2 rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                    <span class="material-symbols-outlined text-base" aria-hidden="true">palette</span>
                    <input type="color" x-model="cv.theme_color" @change="autoSave()" class="h-5 w-6 cursor-pointer border-0 bg-transparent p-0" />
                </label>

                {{-- Share toggle --}}
                <button
                    type="button"
                    @click="togglePublic()"
                    :class="cv.is_public ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-white text-slate-700 ring-slate-200'"
                    class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold ring-1 transition"
                >
                    <span class="material-symbols-outlined text-base" aria-hidden="true" x-text="cv.is_public ? 'public' : 'lock'"></span>
                    <span x-text="cv.is_public ? 'Public' : 'Private'"></span>
                </button>

                <button
                    type="button"
                    @click="copyShareLink()"
                    x-show="cv.is_public"
                    class="inline-flex items-center gap-1 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50"
                >
                    <span class="material-symbols-outlined text-base" aria-hidden="true">link</span>
                    <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                </button>

                <a
                    href="{{ route('cv.download', $cv) }}"
                    class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-primary/90"
                >
                    <span class="material-symbols-outlined text-base" aria-hidden="true">download</span>
                    Download PDF
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.85fr)]">
            {{-- Editor --}}
            <section class="space-y-4">
                {{-- Section tabs --}}
                <div class="flex flex-wrap gap-1 rounded-xl bg-white p-2 shadow-sm ring-1 ring-slate-200 text-sm">
                    @php
                        $tabs = [
                            ['personal', 'Personal', 'person'],
                            ['summary', 'Summary', 'subject'],
                            ['experience', 'Experience', 'work'],
                            ['education', 'Education', 'school'],
                            ['skills', 'Skills', 'psychology'],
                            ['languages', 'Languages', 'translate'],
                            ['certifications', 'Certifications', 'verified'],
                            ['projects', 'Projects', 'folder_open'],
                            ['references', 'References', 'contacts'],
                            ['ats', 'ATS Score', 'auto_awesome'],
                        ];
                    @endphp
                    @foreach ($tabs as [$key, $label, $icon])
                        <button
                            type="button"
                            @click="activeTab = '{{ $key }}'"
                            :class="activeTab === '{{ $key }}' ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-100'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-semibold transition"
                        >
                            <span class="material-symbols-outlined text-base" aria-hidden="true">{{ $icon }}</span>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Personal --}}
                <div x-show="activeTab === 'personal'" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-base font-bold text-slate-900">Personal information</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Who you are and how to reach you.</p>
                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Full name</span>
                            <input type="text" x-model="cv.personal.full_name" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Ahmed Khan" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Headline / Title</span>
                            <input type="text" x-model="cv.personal.headline" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Senior PHP Developer" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Email</span>
                            <input type="email" x-model="cv.personal.email" @input.debounce.500ms="autoSave()" class="cv-input" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Phone</span>
                            <input type="tel" x-model="cv.personal.phone" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="+92 300 1234567" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Location</span>
                            <input type="text" x-model="cv.personal.location" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Lahore, Pakistan" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">Website / Portfolio</span>
                            <input type="url" x-model="cv.personal.website" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="https://ahmed.dev" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">LinkedIn</span>
                            <input type="url" x-model="cv.personal.linkedin" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="https://linkedin.com/in/…" />
                        </label>
                        <label class="block">
                            <span class="text-xs font-semibold text-slate-700">GitHub</span>
                            <input type="url" x-model="cv.personal.github" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="https://github.com/…" />
                        </label>
                    </div>
                </div>

                {{-- Summary --}}
                <div x-show="activeTab === 'summary'" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Professional summary</h2>
                            <p class="mt-0.5 text-xs text-slate-500">3-4 concise sentences that sell you.</p>
                        </div>
                        <button
                            type="button"
                            @click="aiImproveSummary()"
                            :disabled="ai.summary.loading"
                            class="inline-flex items-center gap-1 rounded-lg bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 ring-1 ring-violet-200 transition hover:bg-violet-100 disabled:opacity-50"
                        >
                            <span class="material-symbols-outlined text-base" aria-hidden="true">auto_awesome</span>
                            <span x-text="ai.summary.loading ? 'Improving…' : 'Improve with AI'"></span>
                        </button>
                    </div>
                    <textarea
                        x-model="cv.summary"
                        @input.debounce.500ms="autoSave()"
                        rows="6"
                        class="cv-input mt-4"
                        placeholder="Experienced software engineer with 5+ years building scalable Laravel applications…"
                    ></textarea>
                    <template x-if="ai.summary.error">
                        <p class="mt-2 text-xs text-rose-600" x-text="ai.summary.error"></p>
                    </template>
                </div>

                {{-- Experience --}}
                <div x-show="activeTab === 'experience'" class="space-y-3">
                    <template x-for="(item, idx) in cv.experience" :key="idx">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-700">
                                    Experience #<span x-text="idx + 1"></span>
                                </h3>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveItem('experience', idx, -1)" :disabled="idx === 0" class="cv-icon-btn" title="Move up">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_upward</span>
                                    </button>
                                    <button type="button" @click="moveItem('experience', idx, 1)" :disabled="idx === cv.experience.length - 1" class="cv-icon-btn" title="Move down">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">arrow_downward</span>
                                    </button>
                                    <button type="button" @click="removeItem('experience', idx)" class="cv-icon-btn text-rose-600" title="Remove">
                                        <span class="material-symbols-outlined text-base" aria-hidden="true">delete</span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label><span class="cv-label">Role</span><input type="text" x-model="item.role" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Senior Developer" /></label>
                                <label><span class="cv-label">Company</span><input type="text" x-model="item.company" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="JobsPic" /></label>
                                <label><span class="cv-label">Location</span><input type="text" x-model="item.location" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Remote / Lahore" /></label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label><span class="cv-label">Start</span><input type="text" x-model="item.start" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Jan 2022" /></label>
                                    <label>
                                        <span class="cv-label">End</span>
                                        <input type="text" x-model="item.end" @input.debounce.500ms="autoSave()" :disabled="item.current" class="cv-input disabled:bg-slate-100" placeholder="Present" />
                                    </label>
                                </div>
                            </div>
                            <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                                <input type="checkbox" x-model="item.current" @change="if(item.current) item.end = ''; autoSave()" class="rounded border-slate-300 text-primary focus:ring-primary" />
                                I currently work here
                            </label>

                            <div class="mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="cv-label">Achievements (bullets)</span>
                                    <button
                                        type="button"
                                        @click="aiBullets(idx)"
                                        :disabled="!item.role || !item.company || ai.bullets.loadingIdx === idx"
                                        class="inline-flex items-center gap-1 rounded-lg bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200 transition hover:bg-violet-100 disabled:opacity-50"
                                    >
                                        <span class="material-symbols-outlined text-sm" aria-hidden="true">auto_awesome</span>
                                        <span x-text="ai.bullets.loadingIdx === idx ? 'Generating…' : 'Generate with AI'"></span>
                                    </button>
                                </div>
                                <template x-for="(b, bi) in item.bullets" :key="bi">
                                    <div class="mt-2 flex items-start gap-2">
                                        <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-slate-400"></span>
                                        <input type="text" x-model="item.bullets[bi]" @input.debounce.500ms="autoSave()" class="cv-input flex-1" placeholder="Led migration to Laravel 12, cutting API latency 35%." />
                                        <button type="button" @click="item.bullets.splice(bi, 1); autoSave()" class="cv-icon-btn text-rose-600" title="Remove bullet">
                                            <span class="material-symbols-outlined text-base" aria-hidden="true">close</span>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="item.bullets.push(''); autoSave()" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline">
                                    <span class="material-symbols-outlined text-sm" aria-hidden="true">add</span>
                                    Add bullet
                                </button>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addItem('experience')" class="cv-add-btn">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">add</span>
                        Add experience
                    </button>
                </div>

                {{-- Education --}}
                <div x-show="activeTab === 'education'" class="space-y-3">
                    <template x-for="(item, idx) in cv.education" :key="idx">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-700">
                                    Education #<span x-text="idx + 1"></span>
                                </h3>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveItem('education', idx, -1)" :disabled="idx === 0" class="cv-icon-btn"><span class="material-symbols-outlined text-base">arrow_upward</span></button>
                                    <button type="button" @click="moveItem('education', idx, 1)" :disabled="idx === cv.education.length - 1" class="cv-icon-btn"><span class="material-symbols-outlined text-base">arrow_downward</span></button>
                                    <button type="button" @click="removeItem('education', idx)" class="cv-icon-btn text-rose-600"><span class="material-symbols-outlined text-base">delete</span></button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label><span class="cv-label">Institution</span><input type="text" x-model="item.institution" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="University of Punjab" /></label>
                                <label><span class="cv-label">Degree</span><input type="text" x-model="item.degree" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="BSCS" /></label>
                                <label><span class="cv-label">Field of study</span><input type="text" x-model="item.field" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Computer Science" /></label>
                                <label><span class="cv-label">Location</span><input type="text" x-model="item.location" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Lahore" /></label>
                                <label><span class="cv-label">Start</span><input type="text" x-model="item.start" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="2018" /></label>
                                <label><span class="cv-label">End</span><input type="text" x-model="item.end" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="2022" /></label>
                                <label><span class="cv-label">GPA / Grade (optional)</span><input type="text" x-model="item.gpa" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="3.8 / 4.0" /></label>
                            </div>
                            <label class="mt-3 block">
                                <span class="cv-label">Description (optional)</span>
                                <textarea x-model="item.description" @input.debounce.500ms="autoSave()" rows="2" class="cv-input"></textarea>
                            </label>
                        </div>
                    </template>
                    <button type="button" @click="addItem('education')" class="cv-add-btn">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">add</span>
                        Add education
                    </button>
                </div>

                {{-- Skills --}}
                <div x-show="activeTab === 'skills'" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Skills</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Group by category. AI can suggest based on a target role.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="ai.skills.role" placeholder="Target role" class="cv-input w-40 text-xs" />
                            <button
                                type="button"
                                @click="aiSuggestSkills()"
                                :disabled="!ai.skills.role || ai.skills.loading"
                                class="inline-flex items-center gap-1 rounded-lg bg-violet-50 px-2 py-1 text-xs font-semibold text-violet-700 ring-1 ring-violet-200 transition hover:bg-violet-100 disabled:opacity-50"
                            >
                                <span class="material-symbols-outlined text-sm" aria-hidden="true">auto_awesome</span>
                                <span x-text="ai.skills.loading ? 'Suggesting…' : 'Suggest'"></span>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <template x-for="(s, idx) in cv.skills" :key="idx">
                            <div class="flex items-center gap-2">
                                <select x-model="s.category" @change="autoSave()" class="cv-input w-36 text-xs">
                                    <option>Technical</option>
                                    <option>Tools</option>
                                    <option>Frameworks</option>
                                    <option>Soft</option>
                                    <option>Languages</option>
                                    <option>Other</option>
                                </select>
                                <input type="text" x-model="s.name" @input.debounce.500ms="autoSave()" class="cv-input flex-1" placeholder="PHP / Laravel / React" />
                                <select x-model="s.level" @change="autoSave()" class="cv-input w-28 text-xs">
                                    <option value="">Level…</option>
                                    <option>Beginner</option>
                                    <option>Intermediate</option>
                                    <option>Advanced</option>
                                    <option>Expert</option>
                                </select>
                                <button type="button" @click="removeItem('skills', idx)" class="cv-icon-btn text-rose-600">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addItem('skills')" class="cv-add-btn mt-3">
                        <span class="material-symbols-outlined text-base">add</span>
                        Add skill
                    </button>
                </div>

                {{-- Languages --}}
                <div x-show="activeTab === 'languages'" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-base font-bold text-slate-900">Languages</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Languages you speak, with fluency.</p>
                    <div class="mt-4 space-y-2">
                        <template x-for="(l, idx) in cv.languages" :key="idx">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="l.name" @input.debounce.500ms="autoSave()" class="cv-input flex-1" placeholder="Urdu" />
                                <select x-model="l.level" @change="autoSave()" class="cv-input w-40 text-xs">
                                    <option value="">Fluency…</option>
                                    <option>Native</option>
                                    <option>Fluent</option>
                                    <option>Professional</option>
                                    <option>Conversational</option>
                                    <option>Basic</option>
                                </select>
                                <button type="button" @click="removeItem('languages', idx)" class="cv-icon-btn text-rose-600">
                                    <span class="material-symbols-outlined text-base">delete</span>
                                </button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addItem('languages')" class="cv-add-btn mt-3">
                        <span class="material-symbols-outlined text-base">add</span>
                        Add language
                    </button>
                </div>

                {{-- Certifications --}}
                <div x-show="activeTab === 'certifications'" class="space-y-3">
                    <template x-for="(item, idx) in cv.certifications" :key="idx">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-700">Certification #<span x-text="idx + 1"></span></h3>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveItem('certifications', idx, -1)" :disabled="idx === 0" class="cv-icon-btn"><span class="material-symbols-outlined text-base">arrow_upward</span></button>
                                    <button type="button" @click="moveItem('certifications', idx, 1)" :disabled="idx === cv.certifications.length - 1" class="cv-icon-btn"><span class="material-symbols-outlined text-base">arrow_downward</span></button>
                                    <button type="button" @click="removeItem('certifications', idx)" class="cv-icon-btn text-rose-600"><span class="material-symbols-outlined text-base">delete</span></button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label><span class="cv-label">Name</span><input type="text" x-model="item.name" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="AWS Solutions Architect" /></label>
                                <label><span class="cv-label">Issuer</span><input type="text" x-model="item.issuer" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Amazon Web Services" /></label>
                                <label><span class="cv-label">Date</span><input type="text" x-model="item.date" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Mar 2024" /></label>
                                <label><span class="cv-label">Credential URL (optional)</span><input type="url" x-model="item.url" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="https://…" /></label>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addItem('certifications')" class="cv-add-btn">
                        <span class="material-symbols-outlined text-base">add</span>
                        Add certification
                    </button>
                </div>

                {{-- Projects --}}
                <div x-show="activeTab === 'projects'" class="space-y-3">
                    <template x-for="(item, idx) in cv.projects" :key="idx">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-700">Project #<span x-text="idx + 1"></span></h3>
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="moveItem('projects', idx, -1)" :disabled="idx === 0" class="cv-icon-btn"><span class="material-symbols-outlined text-base">arrow_upward</span></button>
                                    <button type="button" @click="moveItem('projects', idx, 1)" :disabled="idx === cv.projects.length - 1" class="cv-icon-btn"><span class="material-symbols-outlined text-base">arrow_downward</span></button>
                                    <button type="button" @click="removeItem('projects', idx)" class="cv-icon-btn text-rose-600"><span class="material-symbols-outlined text-base">delete</span></button>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label><span class="cv-label">Name</span><input type="text" x-model="item.name" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="JobsPic" /></label>
                                <label><span class="cv-label">Technologies</span><input type="text" x-model="item.technologies" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="Laravel, Tailwind, SQLite" /></label>
                                <label class="sm:col-span-2"><span class="cv-label">URL (optional)</span><input type="url" x-model="item.url" @input.debounce.500ms="autoSave()" class="cv-input" placeholder="https://…" /></label>
                                <label class="sm:col-span-2">
                                    <span class="cv-label">Description</span>
                                    <textarea x-model="item.description" @input.debounce.500ms="autoSave()" rows="3" class="cv-input"></textarea>
                                </label>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addItem('projects')" class="cv-add-btn">
                        <span class="material-symbols-outlined text-base">add</span>
                        Add project
                    </button>
                </div>

                {{-- References --}}
                <div x-show="activeTab === 'references'" class="space-y-3">
                    <template x-for="(item, idx) in cv.references" :key="idx">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-slate-700">Reference #<span x-text="idx + 1"></span></h3>
                                <button type="button" @click="removeItem('references', idx)" class="cv-icon-btn text-rose-600"><span class="material-symbols-outlined text-base">delete</span></button>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label><span class="cv-label">Name</span><input type="text" x-model="item.name" @input.debounce.500ms="autoSave()" class="cv-input" /></label>
                                <label><span class="cv-label">Role</span><input type="text" x-model="item.role" @input.debounce.500ms="autoSave()" class="cv-input" /></label>
                                <label><span class="cv-label">Company</span><input type="text" x-model="item.company" @input.debounce.500ms="autoSave()" class="cv-input" /></label>
                                <label><span class="cv-label">Email</span><input type="email" x-model="item.email" @input.debounce.500ms="autoSave()" class="cv-input" /></label>
                                <label><span class="cv-label">Phone</span><input type="tel" x-model="item.phone" @input.debounce.500ms="autoSave()" class="cv-input" /></label>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addItem('references')" class="cv-add-btn">
                        <span class="material-symbols-outlined text-base">add</span>
                        Add reference
                    </button>
                </div>

                {{-- ATS --}}
                <div x-show="activeTab === 'ats'" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <div class="flex items-start gap-3">
                        <div class="rounded-lg bg-violet-50 p-2 text-violet-600">
                            <span class="material-symbols-outlined text-2xl" aria-hidden="true">auto_awesome</span>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-slate-900">ATS match score</h2>
                            <p class="mt-0.5 text-xs text-slate-500">Paste a job description and see how well your CV matches.</p>
                        </div>
                    </div>
                    <textarea x-model="ai.ats.jobDescription" rows="6" class="cv-input mt-4" placeholder="Paste the full job description here…"></textarea>
                    <button
                        type="button"
                        @click="aiAtsScore()"
                        :disabled="!ai.ats.jobDescription || ai.ats.jobDescription.length < 40 || ai.ats.loading"
                        class="mt-3 inline-flex items-center gap-1 rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700 disabled:opacity-50"
                    >
                        <span class="material-symbols-outlined text-base" aria-hidden="true">analytics</span>
                        <span x-text="ai.ats.loading ? 'Scoring…' : 'Get ATS score'"></span>
                    </button>

                    <template x-if="ai.ats.error">
                        <p class="mt-3 text-xs text-rose-600" x-text="ai.ats.error"></p>
                    </template>

                    <template x-if="ai.ats.result">
                        <div class="mt-5 space-y-4">
                            <div class="flex items-center gap-4 rounded-lg bg-slate-50 p-4">
                                <div class="relative h-20 w-20 shrink-0">
                                    <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                                        <circle cx="18" cy="18" r="15.9" fill="none" stroke="#e2e8f0" stroke-width="3"/>
                                        <circle cx="18" cy="18" r="15.9" fill="none" :stroke="ai.ats.result.score >= 70 ? '#059669' : (ai.ats.result.score >= 40 ? '#d97706' : '#dc2626')" stroke-width="3" :stroke-dasharray="ai.ats.result.score + ', 100'" stroke-linecap="round"/>
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-xl font-black text-slate-900" x-text="ai.ats.result.score"></span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-slate-900">Match score</p>
                                    <p class="mt-0.5 text-xs text-slate-600" x-text="ai.ats.result.score >= 70 ? 'Strong match' : (ai.ats.result.score >= 40 ? 'Moderate — tailor more' : 'Low — major gaps')"></p>
                                </div>
                            </div>

                            <template x-if="ai.ats.result.strengths.length">
                                <div>
                                    <h3 class="text-sm font-bold text-emerald-700">Strengths</h3>
                                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                        <template x-for="(s, i) in ai.ats.result.strengths" :key="i"><li x-text="s"></li></template>
                                    </ul>
                                </div>
                            </template>
                            <template x-if="ai.ats.result.gaps.length">
                                <div>
                                    <h3 class="text-sm font-bold text-amber-700">Gaps</h3>
                                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                        <template x-for="(g, i) in ai.ats.result.gaps" :key="i"><li x-text="g"></li></template>
                                    </ul>
                                </div>
                            </template>
                            <template x-if="ai.ats.result.suggestions.length">
                                <div>
                                    <h3 class="text-sm font-bold text-violet-700">Suggestions</h3>
                                    <ul class="mt-1 list-disc space-y-1 pl-5 text-sm text-slate-700">
                                        <template x-for="(s, i) in ai.ats.result.suggestions" :key="i"><li x-text="s"></li></template>
                                    </ul>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </section>

            {{-- Live preview --}}
            <aside class="lg:sticky lg:top-4 lg:self-start">
                <div class="rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200">
                    <div class="mb-2 flex items-center justify-between text-xs text-slate-500">
                        <span class="font-semibold uppercase tracking-wide">Live preview</span>
                        <button type="button" @click="refreshPreview()" class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 hover:bg-slate-100">
                            <span class="material-symbols-outlined text-sm" aria-hidden="true">refresh</span>
                            Refresh
                        </button>
                    </div>
                    <div class="overflow-hidden rounded-lg ring-1 ring-slate-200">
                        <iframe
                            :src="previewSrc"
                            class="h-[calc(100vh-200px)] w-full bg-white"
                            title="CV preview"
                        ></iframe>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <style>
        .cv-input {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid rgb(226 232 240);
            background: white;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            color: rgb(15 23 42);
        }
        .cv-input:focus {
            outline: none;
            border-color: #004b93;
            box-shadow: 0 0 0 3px rgba(0, 75, 147, 0.1);
        }
        .cv-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: rgb(51 65 85);
            margin-bottom: 0.25rem;
        }
        .cv-icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.25rem;
            border-radius: 0.375rem;
            color: rgb(100 116 139);
            transition: all 0.15s;
        }
        .cv-icon-btn:hover:not(:disabled) {
            background: rgb(241 245 249);
            color: rgb(30 41 59);
        }
        .cv-icon-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }
        .cv-add-btn {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            padding: 0.625rem 1rem;
            border-radius: 0.75rem;
            background: white;
            border: 2px dashed rgb(203 213 225);
            color: rgb(71 85 105);
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.15s;
        }
        .cv-add-btn:hover {
            border-color: #004b93;
            color: #004b93;
            background: rgba(0, 75, 147, 0.02);
        }
    </style>

    <script>
        window.cvInitial = @json($data);
        window.cvId = @json($cv->id);
        window.cvIsPublic = @json((bool) $cv->is_public);
        window.cvShareUrl = @json($publicUrl);
        window.cvPreviewBase = @json(route('cv.preview', $cv));
        window.cvUpdateUrl = @json(route('cv.update', $cv));
        window.cvAiUrls = {
            summary: @json(route('cv.ai.summary', $cv)),
            bullets: @json(route('cv.ai.bullets', $cv)),
            skills: @json(route('cv.ai.skills', $cv)),
            ats: @json(route('cv.ai.ats', $cv)),
        };
        window.csrfToken = @json(csrf_token());

        function cvBuilder() {
            return {
                cv: {
                    title: @json($cv->title),
                    template: @json($cv->template),
                    theme_color: @json($cv->theme_color),
                    font_family: @json($cv->font_family),
                    is_public: @json((bool) $cv->is_public),
                    personal: Object.assign({
                        full_name: '', headline: '', email: '', phone: '',
                        location: '', website: '', linkedin: '', github: '',
                    }, window.cvInitial.personal || {}),
                    summary: window.cvInitial.summary || '',
                    experience: window.cvInitial.experience || [],
                    education: window.cvInitial.education || [],
                    skills: window.cvInitial.skills || [],
                    languages: window.cvInitial.languages || [],
                    certifications: window.cvInitial.certifications || [],
                    projects: window.cvInitial.projects || [],
                    references: window.cvInitial.references || [],
                },
                activeTab: 'personal',
                saveState: 'saved',
                saveTimer: null,
                previewTimer: null,
                previewVersion: 0,
                previewSrc: window.cvPreviewBase + '?v=0',
                copied: false,
                ai: {
                    summary: { loading: false, error: '' },
                    bullets: { loadingIdx: -1, error: '' },
                    skills: { loading: false, error: '', role: '' },
                    ats: { loading: false, error: '', jobDescription: '', result: null },
                },

                init() {},

                buildPayload() {
                    return {
                        title: this.cv.title,
                        template: this.cv.template,
                        theme_color: this.cv.theme_color,
                        font_family: this.cv.font_family,
                        is_public: this.cv.is_public,
                        summary: this.cv.summary,
                        personal: this.cv.personal,
                        experience: this.cv.experience,
                        education: this.cv.education,
                        skills: this.cv.skills,
                        languages: this.cv.languages,
                        certifications: this.cv.certifications,
                        projects: this.cv.projects,
                        references_list: this.cv.references,
                    };
                },

                async autoSave() {
                    this.saveState = 'saving';
                    clearTimeout(this.saveTimer);
                    this.saveTimer = setTimeout(async () => {
                        try {
                            const res = await fetch(window.cvUpdateUrl, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': window.csrfToken,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                credentials: 'same-origin',
                                body: JSON.stringify(this.buildPayload()),
                            });
                            if (!res.ok) throw new Error('save failed');
                            this.saveState = 'saved';
                            this.schedulePreview();
                        } catch (e) {
                            this.saveState = 'error';
                        }
                    }, 400);
                },

                schedulePreview() {
                    clearTimeout(this.previewTimer);
                    this.previewTimer = setTimeout(() => {
                        this.refreshPreview();
                    }, 300);
                },

                refreshPreview() {
                    this.previewVersion += 1;
                    this.previewSrc = window.cvPreviewBase + '?v=' + this.previewVersion;
                },

                addItem(key) {
                    const templates = {
                        experience: { company: '', role: '', location: '', start: '', end: '', current: false, bullets: [''] },
                        education: { institution: '', degree: '', field: '', location: '', start: '', end: '', gpa: '', description: '' },
                        skills: { category: 'Technical', name: '', level: '' },
                        languages: { name: '', level: '' },
                        certifications: { name: '', issuer: '', date: '', url: '' },
                        projects: { name: '', description: '', technologies: '', url: '' },
                        references: { name: '', role: '', company: '', phone: '', email: '' },
                    };
                    this.cv[key].push(JSON.parse(JSON.stringify(templates[key])));
                    this.autoSave();
                },

                removeItem(key, idx) {
                    this.cv[key].splice(idx, 1);
                    this.autoSave();
                },

                moveItem(key, idx, dir) {
                    const arr = this.cv[key];
                    const target = idx + dir;
                    if (target < 0 || target >= arr.length) return;
                    [arr[idx], arr[target]] = [arr[target], arr[idx]];
                    this.autoSave();
                },

                async togglePublic() {
                    this.cv.is_public = !this.cv.is_public;
                    await this.autoSave();
                },

                copyShareLink() {
                    navigator.clipboard.writeText(window.cvShareUrl).then(() => {
                        this.copied = true;
                        setTimeout(() => { this.copied = false; }, 1800);
                    }).catch(() => {});
                },

                async aiCall(url, body) {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': window.csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify(body),
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!res.ok || !json.ok) {
                        const err = json.error || ('Request failed (' + res.status + ')');
                        throw new Error(err);
                    }
                    return json;
                },

                async aiImproveSummary() {
                    this.ai.summary.loading = true;
                    this.ai.summary.error = '';
                    try {
                        const res = await this.aiCall(window.cvAiUrls.summary, {
                            current: this.cv.summary || '',
                            target_role: this.cv.personal.headline || '',
                        });
                        this.cv.summary = res.summary;
                        this.autoSave();
                    } catch (e) {
                        this.ai.summary.error = e.message;
                    } finally {
                        this.ai.summary.loading = false;
                    }
                },

                async aiBullets(idx) {
                    this.ai.bullets.loadingIdx = idx;
                    this.ai.bullets.error = '';
                    try {
                        const item = this.cv.experience[idx];
                        const res = await this.aiCall(window.cvAiUrls.bullets, {
                            role: item.role,
                            company: item.company,
                            context: (item.bullets || []).filter(Boolean).join('\n'),
                        });
                        item.bullets = [...(item.bullets || []).filter(Boolean), ...res.bullets];
                        this.autoSave();
                    } catch (e) {
                        this.ai.bullets.error = e.message;
                    } finally {
                        this.ai.bullets.loadingIdx = -1;
                    }
                },

                async aiSuggestSkills() {
                    this.ai.skills.loading = true;
                    this.ai.skills.error = '';
                    try {
                        const res = await this.aiCall(window.cvAiUrls.skills, { target_role: this.ai.skills.role });
                        const existing = new Set((this.cv.skills || []).map(s => (s.name || '').toLowerCase()));
                        for (const s of res.skills) {
                            if (!existing.has(s.name.toLowerCase())) {
                                this.cv.skills.push({ category: s.category || 'Technical', name: s.name, level: '' });
                            }
                        }
                        this.autoSave();
                    } catch (e) {
                        this.ai.skills.error = e.message;
                    } finally {
                        this.ai.skills.loading = false;
                    }
                },

                async aiAtsScore() {
                    this.ai.ats.loading = true;
                    this.ai.ats.error = '';
                    this.ai.ats.result = null;
                    try {
                        await this.autoSave();
                        const res = await this.aiCall(window.cvAiUrls.ats, { job_description: this.ai.ats.jobDescription });
                        this.ai.ats.result = {
                            score: res.score,
                            strengths: res.strengths || [],
                            gaps: res.gaps || [],
                            suggestions: res.suggestions || [],
                        };
                    } catch (e) {
                        this.ai.ats.error = e.message;
                    } finally {
                        this.ai.ats.loading = false;
                    }
                },
            };
        }
    </script>
</x-layout>
