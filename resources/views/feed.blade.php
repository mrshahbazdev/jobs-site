@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" xmlns:slash="http://purl.org/rss/1.0/modules/slash/">
<channel>
    <title>JobsPic - Latest Jobs in Pakistan</title>
    <atom:link href="{{ url('/feed') }}" rel="self" type="application/rss+xml" />
    <link>{{ url('/') }}</link>
    <description>Find the newest Government and Private job openings in Pakistan.</description>
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
    <language>en-US</language>
    <sy:updatePeriod>hourly</sy:updatePeriod>
    <sy:updateFrequency>1</sy:updateFrequency>

    @foreach($jobs as $job)
    <item>
        <title>{{ $job->title }}</title>
        <link>{{ route('jobs.show', $job->slug) }}</link>
        <pubDate>{{ $job->created_at->toRfc2822String() }}</pubDate>
        <dc:creator><![CDATA[JobsPic]]></dc:creator>
        <category><![CDATA[{{ $job->category ? $job->category->name : 'General' }}]]></category>
        <guid isPermaLink="false">{{ route('jobs.show', $job->slug) }}</guid>
        <description><![CDATA[{!! \Illuminate\Support\Str::limit(strip_tags($job->description_html), 300) !!}]]></description>
        <content:encoded><![CDATA[{!! $job->description_html !!}]]></content:encoded>
    </item>
    @endforeach
</channel>
</rss>
