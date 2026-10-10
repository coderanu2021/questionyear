<article class="blog-card">
    <a class="blog-card-cover" href="{{ route('blogs.show', $blog->slug) }}" aria-label="{{ $blog->title }}">
        @if($thumbnail = $blog->thumbnailUrl())
        <img src="{{ $thumbnail }}" alt="{{ $blog->title }}" loading="lazy" decoding="async" width="640" height="360">
        @else
        <div class="blog-card-placeholder"><span>questionyear</span><svg viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M32 17C24 11 14 11 6 14v36c8-3 18-3 26 3 8-6 18-6 26-3V14c-8-3-18-3-26 3Zm0 0v36M14 24h10M14 32h10M40 24h10M40 32h10"/></svg><b>STUDY &amp; LEARNING</b></div>
        @endif
    </a>
    <div class="blog-card-body">
        <span class="blog-label">STUDY &amp; LEARNING</span>
        <h3><a href="{{ route('blogs.show', $blog->slug) }}">{{ $blog->title }}</a></h3>
        <p>{{ $blog->previewText() }}</p>
        <div class="blog-card-footer"><time datetime="{{ $blog->published_at->toDateString() }}">{{ $blog->published_at->format('d M Y') }}</time><span>{{ $blog->readingMinutes() }} min read</span></div>
        <a class="blog-card-link" href="{{ route('blogs.show', $blog->slug) }}">Read article <span aria-hidden="true">→</span></a>
    </div>
</article>
