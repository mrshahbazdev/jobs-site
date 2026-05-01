@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@forelse ($jobs as $job)
    <url>
        <loc>{{ route('jobs.show', $job->slug) }}</loc>
        <image:image>
            <image:loc>{{ asset('storage/'.$job->sourceImage->local_image_path) }}</image:loc>
            <image:title>{{ htmlspecialchars($job->title, ENT_XML1, 'UTF-8') }} Advertisement</image:title>
            <image:caption>Check latest {{ htmlspecialchars($job->title, ENT_XML1, 'UTF-8') }} in {{ $job->city->name ?? 'Pakistan' }} - JobsPic Pakistan</image:caption>
        </image:image>
    </url>
@empty
    <url>
        <loc>{{ url('/') }}</loc>
    </url>
@endforelse
</urlset>
