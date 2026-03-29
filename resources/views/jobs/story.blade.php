<!doctype html>
<html amp lang="en">
  <head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-story" src="https://cdn.ampproject.org/v0/amp-story-1.0.js"></script>
    <title>{{ $job->title }} - Web Story</title>
    <link rel="canonical" href="{{ route('jobs.show', $job->slug) }}">
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    <style amp-custom>
      amp-story { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
      amp-story-page { background-color: #004b93; }
      h1 { font-weight: 900; font-size: 32px; line-height: 1.1; color: #fff; text-transform: uppercase; letter-spacing: -1px; }
      .category-tag { background: #fff; color: #004b93; display: inline-block; padding: 5px 15px; border-radius: 50px; font-weight: 900; font-size: 14px; margin-bottom: 20px; text-transform: uppercase; }
      .meta-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 20px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.2); }
      .meta-item { color: #fff; margin-bottom: 10px; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
      .cta-container { text-align: center; width: 100%; }
      .swipe-up { color: #fff; font-weight: 900; font-size: 16px; animation: bounce 2s infinite; }
      @keyframes bounce { 0%, 20%, 50%, 80%, 100% {transform: translateY(0);} 40% {transform: translateY(-10px);} 60% {transform: translateY(-5px);} }
    </style>
  </head>
  <body>
    <amp-story standalone title="{{ $job->title }}" publisher="JobsPic" publisher-logo-src="{{ asset('icons/icon-192x192.png') }}" poster-portrait-src="{{ asset('icons/icon-512x512.png') }}">
      
      <!-- Page 1: Cover -->
      <amp-story-page id="cover">
        <amp-story-grid-layer template="vertical" class="bg-layer">
            <div style="background: linear-gradient(135deg, #004b93 0%, #001f3f 100%); width: 100%; height: 100%;"></div>
        </amp-story-grid-layer>
        <amp-story-grid-layer template="vertical" style="align-content: center; padding: 40px;">
          <div animate-in="fade-in" animate-in-delay="0.2s">
            <span class="category-tag">{{ $job->category->name }}</span>
          </div>
          <h1 animate-in="fly-in-bottom">{{ $job->title }}</h1>
          <p style="color: rgba(255,255,255,0.7); font-weight: 700;" animate-in="fade-in" animate-in-delay="0.5s">📍 {{ $job->city->name }}</p>
        </amp-story-grid-layer>
      </amp-story-page>

      <!-- Page 2: Details -->
      <amp-story-page id="details">
        <amp-story-grid-layer template="vertical" class="bg-layer">
            <div style="background: linear-gradient(225deg, #1e293b 0%, #0f172a 100%); width: 100%; height: 100%;"></div>
        </amp-story-grid-layer>
        <amp-story-grid-layer template="vertical" style="align-content: center; padding: 30px;">
          <h2 style="color: #60a5fa; font-black: 900; margin-bottom: 20px; font-size: 24px;" animate-in="scale-in">JOB DETAILS</h2>
          <div class="meta-box" animate-in="fade-in" animate-in-delay="0.3s">
            <div class="meta-item">💼 {{ $job->job_type ?? 'Full Time' }}</div>
            <div class="meta-item">🎓 {{ $job->education ?? 'Check Details' }}</div>
            <div class="meta-item">💰 {{ $job->salary_range ?? 'Market Competitive' }}</div>
            <div class="meta-item">📅 Apply by {{ $job->deadline ? \Carbon\Carbon::parse($job->deadline)->format('M d') : 'Soon' }}</div>
          </div>
        </amp-story-grid-layer>
      </amp-story-page>

      <!-- Page 3: Call to Action -->
      <amp-story-page id="cta">
        <amp-story-grid-layer template="vertical" class="bg-layer">
            <div style="background: linear-gradient(to top, #004b93 0%, #1e293b 100%); width: 100%; height: 100%;"></div>
        </amp-story-grid-layer>
        <amp-story-grid-layer template="vertical" style="align-content: center; text-align: center; padding: 40px;">
          <div animate-in="rotate-in-left">
            <h2 style="color: #fff; font-size: 32px; font-weight: 900; margin-bottom: 10px;">READY TO APPLY?</h2>
            <p style="color: rgba(255,255,255,0.8); font-weight: 600; margin-bottom: 40px;">Don't miss this opportunity in {{ $job->city->name }}!</p>
          </div>
          <div class="swipe-up">
            <div style="font-size: 40px;">☝️</div>
            TAP TO APPLY NOW
          </div>
        </amp-story-grid-layer>
        <amp-story-cta-layer>
          <a href="{{ route('jobs.show', $job->slug) }}" class="btn" style="background: #fff; color: #004b93; font-weight: 900; padding: 15px 30px; border-radius: 12px; text-decoration: none; display: inline-block;">View Original Job Listing</a>
        </amp-story-cta-layer>
      </amp-story-page>

    </amp-story>
  </body>
</html>
