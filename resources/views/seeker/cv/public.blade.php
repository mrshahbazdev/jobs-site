<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $data['personal']['full_name'] ?: $cv->title }} — CV</title>
    @include('seeker.cv.partials._styles')
    <style>
        body { padding: 24px 12px; }
        .cv-page { box-shadow: 0 10px 40px rgba(15, 23, 42, 0.08); border-radius: 12px; }
        .cv-brand {
            max-width: 820px;
            margin: 0 auto 12px;
            font-size: 11px;
            color: #94a3b8;
            text-align: center;
        }
        .cv-brand a { color: var(--accent, #004b93); font-weight: 600; text-decoration: none; }
    </style>
</head>
<body style="--accent: {{ $cv->theme_color }};">
    <div class="cv-brand">
        Built with <a href="{{ url('/') }}">JobsPic CV Builder</a>
    </div>
    <div class="cv-page">
        @include('seeker.cv.partials._render')
    </div>
</body>
</html>
