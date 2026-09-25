<x-layout>
    @section('title', $post->title . ' | JobsPic')
    @section('meta_description', $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->content), 155))
    @section('og_title', $post->title . ' | JobsPic')
    @section('og_type', 'article')
    @section('og_image', $post->poster_path
        ? asset('storage/'.$post->poster_path)
        : ($post->image ? asset('storage/'.$post->image) : asset('icons/icon-512x512.png')))

    @push('styles')<link rel="stylesheet" href="{{ asset('css/blog-article.css') }}?v=1">@endpush

    <div id="jp-progress"></div>
    @php $mins = max(1, (int) ceil(str_word_count(strip_tags($post->content)) / 200)); @endphp

    <div class="jp-post">
        <header class="jp-post-head">
            <span class="jp-cat">Career Guide</span>
            <h1>{{ $post->title }}</h1>
            <div class="jp-meta">
                <span>📅 Updated {{ $post->updated_at->format('d M Y') }}</span>
                <span>⏱ {{ $mins }} min read</span>
                <span>✍️ JobsPic Editorial</span>
            </div>
            @php $postImage = $post->poster_path ?: $post->image; @endphp
            @if($postImage)
                <img src="{{ asset('storage/'.$postImage) }}" alt="{{ $post->title }}" width="1200" height="630" fetchpriority="high">
            @endif
        </header>

        <div class="jp-main" style="min-width:0">
        <article class="jp-article" id="jp-article">
            {!! $post->content !!}
        </article>

        <div class="jp-share">
            @php $u = urlencode(url()->current()); $t = urlencode($post->title); @endphp
            <a class="wa" href="https://wa.me/?text={{ $t }}%20{{ $u }}" target="_blank" rel="noopener">WhatsApp</a>
            <a class="fb" href="https://www.facebook.com/sharer/sharer.php?u={{ $u }}" target="_blank" rel="noopener">Facebook</a>
            <a class="x"  href="https://twitter.com/intent/tweet?url={{ $u }}&text={{ $t }}" target="_blank" rel="noopener">X</a>
        </div>
        </div>

        <aside class="jp-aside">
            <nav class="jp-toc" id="jp-toc" aria-label="Table of contents">
                <h4>On this page</h4><ol></ol>
            </nav>
        </aside>
    </div>

    @push('scripts')
    <script>
    (function(){
        const art=document.getElementById('jp-article'); if(!art) return;
        const slug=s=>s.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');

        // 1) wrap tables for mobile scroll (old posts)
        art.querySelectorAll('table').forEach(t=>{
            if(!t.parentElement.classList.contains('jp-table-wrap')){
                const w=document.createElement('div');w.className='jp-table-wrap';
                t.parentElement.style.overflowX==='auto'?t.parentElement.replaceWith(w):t.replaceWith(w);
                w.appendChild(t);
            }
            t.removeAttribute('border');t.removeAttribute('cellpadding');t.removeAttribute('style');
        });

        // 2) FAQ h3+p → accordion
        const faqH=[...art.querySelectorAll('h2')].find(h=>/frequently asked|faq/i.test(h.textContent));
        if(faqH){
            const box=document.createElement('div');box.className='jp-faq';
            let n=faqH.nextElementSibling;
            while(n && n.tagName==='H3'){
                const q=n, a=[];let m=q.nextElementSibling;
                while(m && !['H2','H3'].includes(m.tagName)){a.push(m);m=m.nextElementSibling;}
                const d=document.createElement('details'),s=document.createElement('summary'),c=document.createElement('div');
                s.textContent=q.textContent;a.forEach(x=>c.appendChild(x));d.append(s,c);box.appendChild(d);q.remove();n=m;
            }
            faqH.after(box);
        }

        // 3) TOC from h2
        const list=document.querySelector('#jp-toc ol'),hs=[...art.querySelectorAll('h2')];
        if(!hs.length){document.getElementById('jp-toc').remove();}
        hs.forEach(h=>{h.id=h.id||slug(h.textContent);
            const li=document.createElement('li');li.innerHTML=`<a href="#${h.id}">${h.textContent}</a>`;list.appendChild(li);});
        const links=[...list.querySelectorAll('a')];
        hs.forEach(h=>new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){
            links.forEach(a=>a.classList.toggle('active',a.getAttribute('href')==='#'+h.id));}}),{rootMargin:'-80px 0px -70% 0px'}).observe(h));

        // 4) reading progress
        const bar=document.getElementById('jp-progress');
        addEventListener('scroll',()=>{const r=art.getBoundingClientRect(),h=art.offsetHeight-innerHeight;
            bar.style.width=Math.min(100,Math.max(0,(-r.top/h)*100))+'%';},{passive:true});
    })();
    </script>
    @endpush
</x-layout>
