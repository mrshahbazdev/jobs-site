@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($jobs as $job)
  @if($job->sourceImage)
    <url>
        <loc>{{ route('jobs.show', $job->slug) }}</loc>
        <image:image>
            <image:loc>{{ asset('storage/'.$job->sourceImage->image_path) }}</image:loc>
            <image:title>{{ $job->title }} Advertisement</image:title>
            <image:caption>Check latest {{ $job->title }} in {{ $job->city->name }} - JobsPic Pakistan</image:caption>
        </image:image>
    </url>
  @endif
@endforeach
</urlset>
