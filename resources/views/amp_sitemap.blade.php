@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($jobs as $job)
    <url>
        <loc>{{ route('jobs.amp', $job->slug) }}</loc>
        <xhtml:link rel="canonical" href="{{ route('jobs.show', $job->slug) }}" />
        <lastmod>{{ $job->updated_at->toW3cString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
@endforeach
</urlset>
