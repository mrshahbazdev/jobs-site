<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $data['personal']['full_name'] ?: 'CV' }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 28px 32px; font-family: DejaVu Sans, Helvetica, sans-serif; color: #0f172a; font-size: 11pt; line-height: 1.45; }
        h1 { margin: 0; font-size: 20pt; font-weight: bold; }
        .headline { font-size: 11pt; color: #475569; margin-top: 2px; }
        .header { padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; margin-bottom: 10px; }
        .contacts { font-size: 9pt; color: #475569; margin-top: 4px; }
        .contacts span { margin-right: 10px; }
        h2 { font-size: 11pt; font-weight: bold; color: #0f172a; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; margin: 14px 0 6px 0; }
        .entry { margin-bottom: 8px; }
        .entry-head table { width: 100%; border-collapse: collapse; }
        .entry-head .role { font-weight: bold; font-size: 10.5pt; color: #0f172a; }
        .entry-head .company { font-size: 10pt; color: #475569; }
        .entry-head .dates { font-size: 9pt; color: #64748b; text-align: right; white-space: nowrap; }
        ul { margin: 4px 0 0 0; padding-left: 18px; }
        li { font-size: 10pt; margin-bottom: 2px; }
        .text, .summary { font-size: 10pt; color: #334155; margin: 3px 0 0 0; }
        .skill-row { font-size: 10pt; color: #334155; margin-bottom: 3px; }
        .skill-cat { font-weight: bold; color: #0f172a; }
    </style>
</head>
<body>
    @include('seeker.cv.pdf._body')
</body>
</html>
