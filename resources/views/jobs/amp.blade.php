<!doctype html>
<html amp lang="en">
  <head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <title>{{ $job->title }} - JobsPic AMP</title>
    <link rel="canonical" href="{{ route('jobs.show', $job->slug) }}">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <style amp-custom>
      body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; padding: 0; margin: 0; background: #f8fafc; }
      .header { background: #fff; padding: 15px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
      .logo { font-weight: 900; font-size: 20px; text-decoration: none; color: #0f172a; }
      .logo span { color: #004b93; }
      .container { padding: 20px; max-width: 600px; margin: 0 auto; }
      .card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
      .job-header { padding: 25px; border-bottom: 1px solid #f1f5f9; }
      .category { background: #004b93; color: #fff; font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 50px; display: inline-block; text-transform: uppercase; margin-bottom: 10px; }
      h1 { font-size: 24px; font-weight: 900; margin: 0 0 10px; color: #0f172a; line-height: 1.2; }
      .meta { font-size: 13px; color: #64748b; font-weight: 600; }
      .content { padding: 25px; font-size: 16px; }
      .content h2 { font-size: 18px; color: #1e293b; margin-top: 20px; }
      .footer-cta { padding: 25px; background: #f1f5f9; text-align: center; }
      .btn { display: block; background: #004b93; color: #fff; text-decoration: none; padding: 15px; border-radius: 8px; font-weight: 800; font-size: 16px; margin-bottom: 10px; }
      .btn-wa { background: #075E54; }
      .related { margin-top: 30px; }
      .related h3 { font-size: 16px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 15px; }
      .related-card { background: #fff; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 10px; text-decoration: none; display: block; }
      .related-card h4 { margin: 0; color: #334155; font-size: 14px; }
    </style>
  </head>
  <body>
    <header class="header">
      <a href="{{ url('/') }}" class="logo">Jobs<span>Pic</span>.com</a>
    </header>

    <div class="container">
      <div class="card">
        <div class="job-header">
          <div class="category">{{ $job->category->name }}</div>
          <h1>{{ $job->title }}</h1>
          <div class="meta">📍 {{ $job->city->name }} • 📅 Published {{ $job->created_at->format('M d, Y') }}</div>
        </div>

        <div class="content">
          {!! strip_tags($job->description_html, '<p><br><ul><li><strong><b><i><h2><h3><h4>') !!}
        </div>

        <div class="footer-cta">
          <a href="{{ route('jobs.show', $job->slug) }}" class="btn">Apply Full Details</a>
          @if($job->whatsapp_number)
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $job->whatsapp_number) }}" class="btn btn-wa">Apply on WhatsApp</a>
          @endif
        </div>
      </div>

      <div class="related">
        <h3>Related Jobs</h3>
        @foreach($relatedJobs as $rJob)
          <a href="{{ route('jobs.amp', $rJob->slug) }}" class="related-card">
            <h4>{{ $rJob->title }}</h4>
          </a>
        @endforeach
      </div>
    </div>
  </body>
</html>
