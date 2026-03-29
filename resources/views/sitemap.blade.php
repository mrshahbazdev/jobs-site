@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toDateString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    @foreach ($jobs as $job)
        <url>
            <loc>{{ route('jobs.show', ['slug' => $job->slug]) }}</loc>
            <lastmod>{{ $job->updated_at->toDateString() }}</lastmod>
            <changefreq>weekly</changefreq>
            <priority>0.9</priority>
        </url>
    @endforeach

    @foreach ($categories as $category)
        <url>
            <loc>{{ route('categories.show', ['slug' => $category->slug]) }}</loc>
            <lastmod>{{ now()->toDateString() }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    @foreach ($cities as $city)
        <url>
            <loc>{{ route('cities.show', ['slug' => $city->slug]) }}</loc>
            <lastmod>{{ now()->toDateString() }}</lastmod>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
    @endforeach

    <url><loc>{{ route('jobs.all_lists') }}</loc><priority>0.8</priority></url>
    <url><loc>{{ route('pages.about') }}</loc><priority>0.5</priority></url>
    <url><loc>{{ route('pages.privacy') }}</loc><priority>0.3</priority></url>
    <url><loc>{{ route('pages.terms') }}</loc><priority>0.3</priority></url>
</urlset>
