@php
    /** @var \App\Models\Cv $cv */
    /** @var array $data */
    $p = $data['personal'];
@endphp

<div class="header">
    <h1>{{ $p['full_name'] ?: 'Your name' }}</h1>
    @if (!empty($p['headline']))
        <div class="headline">{{ $p['headline'] }}</div>
    @endif
    <div class="contacts">
        @if (!empty($p['email']))<span>{{ $p['email'] }}</span>@endif
        @if (!empty($p['phone']))<span>{{ $p['phone'] }}</span>@endif
        @if (!empty($p['location']))<span>{{ $p['location'] }}</span>@endif
        @if (!empty($p['website']))<span>{{ $p['website'] }}</span>@endif
        @if (!empty($p['linkedin']))<span>{{ str_replace(['https://','http://','www.'], '', $p['linkedin']) }}</span>@endif
        @if (!empty($p['github']))<span>{{ str_replace(['https://','http://','www.'], '', $p['github']) }}</span>@endif
    </div>
</div>

@foreach ($data['section_order'] as $section)
    @switch($section)
        @case('summary')
            @if (!empty($data['summary']))
                <h2>Professional Summary</h2>
                <div class="summary">{{ $data['summary'] }}</div>
            @endif
            @break

        @case('experience')
            @if (count($data['experience']) > 0)
                <h2>Work Experience</h2>
                @foreach ($data['experience'] as $e)
                    <div class="entry">
                        <div class="entry-head">
                            <table>
                                <tr>
                                    <td>
                                        <div class="role">{{ $e['role'] ?: 'Role' }}</div>
                                        <div class="company">{{ $e['company'] }}@if (!empty($e['location'])) • {{ $e['location'] }}@endif</div>
                                    </td>
                                    <td class="dates">
                                        {{ $e['start'] }}@if ($e['start'] || $e['end'] || $e['current']) — @endif{{ $e['current'] ? 'Present' : $e['end'] }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                        @if (count(array_filter($e['bullets'] ?? [], fn ($b) => trim($b) !== '')))
                            <ul>
                                @foreach ($e['bullets'] as $b)
                                    @if (trim($b) !== '')<li>{{ $b }}</li>@endif
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endforeach
            @endif
            @break

        @case('education')
            @if (count($data['education']) > 0)
                <h2>Education</h2>
                @foreach ($data['education'] as $e)
                    <div class="entry">
                        <div class="entry-head">
                            <table>
                                <tr>
                                    <td>
                                        <div class="role">{{ $e['degree'] }}@if (!empty($e['field'])), {{ $e['field'] }}@endif</div>
                                        <div class="company">{{ $e['institution'] }}@if (!empty($e['location'])) • {{ $e['location'] }}@endif</div>
                                    </td>
                                    <td class="dates">
                                        {{ $e['start'] }}@if ($e['start'] || $e['end']) — @endif{{ $e['end'] }}
                                        @if (!empty($e['gpa']))<br>GPA {{ $e['gpa'] }}@endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        @if (!empty($e['description']))
                            <div class="text">{{ $e['description'] }}</div>
                        @endif
                    </div>
                @endforeach
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
                <h2>Skills</h2>
                @foreach ($skillsByCat as $cat => $list)
                    <div class="skill-row">
                        <span class="skill-cat">{{ $cat }}:</span>
                        @foreach ($list as $i => $s)
                            {{ $s['name'] }}@if (!empty($s['level'])) ({{ $s['level'] }})@endif@if ($i < count($list) - 1), @endif
                        @endforeach
                    </div>
                @endforeach
            @endif
            @break

        @case('languages')
            @if (count(array_filter($data['languages'], fn ($l) => trim($l['name'] ?? '') !== '')) > 0)
                <h2>Languages</h2>
                <div class="skill-row">
                    @foreach ($data['languages'] as $i => $l)
                        @if (trim($l['name'] ?? '') !== ''){{ $l['name'] }}@if (!empty($l['level'])) ({{ $l['level'] }})@endif@if ($i < count($data['languages']) - 1) • @endif@endif
                    @endforeach
                </div>
            @endif
            @break

        @case('certifications')
            @if (count(array_filter($data['certifications'], fn ($c) => trim($c['name'] ?? '') !== '')) > 0)
                <h2>Certifications</h2>
                @foreach ($data['certifications'] as $c)
                    @if (trim($c['name'] ?? '') !== '')
                        <div class="entry">
                            <div class="entry-head">
                                <table>
                                    <tr>
                                        <td>
                                            <div class="role">{{ $c['name'] }}</div>
                                            <div class="company">{{ $c['issuer'] }}</div>
                                        </td>
                                        <td class="dates">{{ $c['date'] }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
            @break

        @case('projects')
            @if (count(array_filter($data['projects'], fn ($p) => trim($p['name'] ?? '') !== '')) > 0)
                <h2>Projects</h2>
                @foreach ($data['projects'] as $proj)
                    @if (trim($proj['name'] ?? '') !== '')
                        <div class="entry">
                            <div class="entry-head">
                                <table>
                                    <tr>
                                        <td>
                                            <div class="role">{{ $proj['name'] }}</div>
                                            @if (!empty($proj['technologies']))
                                                <div class="company">{{ $proj['technologies'] }}</div>
                                            @endif
                                        </td>
                                        @if (!empty($proj['url']))
                                            <td class="dates">{{ str_replace(['https://','http://','www.'], '', $proj['url']) }}</td>
                                        @endif
                                    </tr>
                                </table>
                            </div>
                            @if (!empty($proj['description']))
                                <div class="text">{{ $proj['description'] }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
            @break

        @case('references')
            @if (count(array_filter($data['references'], fn ($r) => trim($r['name'] ?? '') !== '')) > 0)
                <h2>References</h2>
                @foreach ($data['references'] as $r)
                    @if (trim($r['name'] ?? '') !== '')
                        <div class="entry">
                            <div class="role">{{ $r['name'] }}</div>
                            <div class="company">{{ $r['role'] }}@if (!empty($r['company'])), {{ $r['company'] }}@endif</div>
                            <div class="text">
                                @if (!empty($r['email'])){{ $r['email'] }}@endif@if (!empty($r['phone'])) • {{ $r['phone'] }}@endif
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
            @break
    @endswitch
@endforeach
