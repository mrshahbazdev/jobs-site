@php
    /** @var array $data */
    /** @var \App\Models\Cv $cv */
    $color = $cv->theme_color ?: '#004b93';
    $template = $cv->template ?: 'modern';
    $p = $data['personal'];
@endphp

<div class="cv-doc cv-t-{{ $template }}" style="--accent: {{ $color }};">
    {{-- Header --}}
    <header class="cv-header">
        <div class="cv-header-main">
            <h1 class="cv-name">{{ $p['full_name'] ?: 'Your name' }}</h1>
            @if (!empty($p['headline']))
                <p class="cv-headline">{{ $p['headline'] }}</p>
            @endif
        </div>
        <div class="cv-contacts">
            @if (!empty($p['email']))<span>✉ {{ $p['email'] }}</span>@endif
            @if (!empty($p['phone']))<span>☎ {{ $p['phone'] }}</span>@endif
            @if (!empty($p['location']))<span>⚲ {{ $p['location'] }}</span>@endif
            @if (!empty($p['website']))<span>{{ $p['website'] }}</span>@endif
            @if (!empty($p['linkedin']))<span>{{ str_replace(['https://','http://','www.'], '', $p['linkedin']) }}</span>@endif
            @if (!empty($p['github']))<span>{{ str_replace(['https://','http://','www.'], '', $p['github']) }}</span>@endif
        </div>
    </header>

    @foreach ($data['section_order'] as $section)
        @switch($section)
            @case('summary')
                @if (!empty($data['summary']))
                    <section class="cv-section">
                        <h2 class="cv-h2">Professional Summary</h2>
                        <p class="cv-summary">{{ $data['summary'] }}</p>
                    </section>
                @endif
                @break

            @case('experience')
                @if (count($data['experience']) > 0)
                    <section class="cv-section">
                        <h2 class="cv-h2">Work Experience</h2>
                        @foreach ($data['experience'] as $e)
                            <div class="cv-entry">
                                <div class="cv-entry-head">
                                    <div>
                                        <div class="cv-role">{{ $e['role'] ?: 'Role' }}</div>
                                        <div class="cv-company">
                                            {{ $e['company'] }}@if (!empty($e['location'])) • {{ $e['location'] }}@endif
                                        </div>
                                    </div>
                                    <div class="cv-dates">
                                        {{ $e['start'] }}@if ($e['start'] || $e['end'] || $e['current']) — @endif{{ $e['current'] ? 'Present' : $e['end'] }}
                                    </div>
                                </div>
                                @if (count($e['bullets'] ?? []))
                                    <ul class="cv-bullets">
                                        @foreach ($e['bullets'] as $b)
                                            @if (trim($b) !== '')
                                                <li>{{ $b }}</li>
                                            @endif
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
                @break

            @case('education')
                @if (count($data['education']) > 0)
                    <section class="cv-section">
                        <h2 class="cv-h2">Education</h2>
                        @foreach ($data['education'] as $e)
                            <div class="cv-entry">
                                <div class="cv-entry-head">
                                    <div>
                                        <div class="cv-role">
                                            {{ $e['degree'] }}@if (!empty($e['field'])), {{ $e['field'] }}@endif
                                        </div>
                                        <div class="cv-company">
                                            {{ $e['institution'] }}@if (!empty($e['location'])) • {{ $e['location'] }}@endif
                                        </div>
                                    </div>
                                    <div class="cv-dates">
                                        {{ $e['start'] }}@if ($e['start'] || $e['end']) — @endif{{ $e['end'] }}
                                        @if (!empty($e['gpa']))<br>GPA {{ $e['gpa'] }}@endif
                                    </div>
                                </div>
                                @if (!empty($e['description']))
                                    <p class="cv-text">{{ $e['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
                @break

            @case('skills')
                @if (count($data['skills']) > 0)
                    @php
                        $skillsByCat = [];
                        foreach ($data['skills'] as $s) {
                            if (trim($s['name'] ?? '') === '') continue;
                            $cat = $s['category'] ?: 'Other';
                            $skillsByCat[$cat][] = $s;
                        }
                    @endphp
                    <section class="cv-section">
                        <h2 class="cv-h2">Skills</h2>
                        @foreach ($skillsByCat as $cat => $list)
                            <div class="cv-skill-row">
                                <span class="cv-skill-cat">{{ $cat }}:</span>
                                <span class="cv-skill-list">
                                    @foreach ($list as $i => $s)
                                        {{ $s['name'] }}@if (!empty($s['level'])) ({{ $s['level'] }})@endif@if ($i < count($list) - 1), @endif
                                    @endforeach
                                </span>
                            </div>
                        @endforeach
                    </section>
                @endif
                @break

            @case('languages')
                @if (count($data['languages']) > 0)
                    <section class="cv-section">
                        <h2 class="cv-h2">Languages</h2>
                        <div class="cv-skill-row">
                            @foreach ($data['languages'] as $i => $l)
                                @if (trim($l['name'] ?? '') !== '')
                                    {{ $l['name'] }}@if (!empty($l['level'])) ({{ $l['level'] }})@endif@if ($i < count($data['languages']) - 1) • @endif
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif
                @break

            @case('certifications')
                @if (count($data['certifications']) > 0)
                    <section class="cv-section">
                        <h2 class="cv-h2">Certifications</h2>
                        @foreach ($data['certifications'] as $c)
                            @if (trim($c['name'] ?? '') !== '')
                                <div class="cv-entry">
                                    <div class="cv-entry-head">
                                        <div>
                                            <div class="cv-role">{{ $c['name'] }}</div>
                                            <div class="cv-company">{{ $c['issuer'] }}</div>
                                        </div>
                                        <div class="cv-dates">{{ $c['date'] }}</div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </section>
                @endif
                @break

            @case('projects')
                @if (count($data['projects']) > 0)
                    <section class="cv-section">
                        <h2 class="cv-h2">Projects</h2>
                        @foreach ($data['projects'] as $proj)
                            @if (trim($proj['name'] ?? '') !== '')
                                <div class="cv-entry">
                                    <div class="cv-entry-head">
                                        <div>
                                            <div class="cv-role">{{ $proj['name'] }}</div>
                                            @if (!empty($proj['technologies']))
                                                <div class="cv-company">{{ $proj['technologies'] }}</div>
                                            @endif
                                        </div>
                                        @if (!empty($proj['url']))
                                            <div class="cv-dates">{{ str_replace(['https://','http://','www.'], '', $proj['url']) }}</div>
                                        @endif
                                    </div>
                                    @if (!empty($proj['description']))
                                        <p class="cv-text">{{ $proj['description'] }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </section>
                @endif
                @break

            @case('references')
                @if (count($data['references']) > 0)
                    <section class="cv-section">
                        <h2 class="cv-h2">References</h2>
                        @foreach ($data['references'] as $r)
                            @if (trim($r['name'] ?? '') !== '')
                                <div class="cv-entry">
                                    <div class="cv-role">{{ $r['name'] }}</div>
                                    <div class="cv-company">
                                        {{ $r['role'] }}@if (!empty($r['company'])), {{ $r['company'] }}@endif
                                    </div>
                                    <div class="cv-text">
                                        @if (!empty($r['email'])){{ $r['email'] }}@endif
                                        @if (!empty($r['phone'])) • {{ $r['phone'] }}@endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </section>
                @endif
                @break
        @endswitch
    @endforeach
</div>
