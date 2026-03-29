@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
@foreach ($jobs as $job)
    <url>
        <loc>{{ route('jobs.show', $job->slug) }}</loc>
        <news:news>
            <news:publication>
                <news:name>JobsPic</news:name>
                <news:language>en</news:language>
            </news:publication>
            <news:publication_date>{{ $job->created_at->toW3cString() }}</news:publication_date>
            <news:title>{{ $job->title }}</news:title>
        </news:news>
    </url>
@endforeach
</urlset>
