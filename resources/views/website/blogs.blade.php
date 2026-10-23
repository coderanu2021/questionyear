@php
    $isNews = request()->routeIs('news.*');
    $listingTitle = $isNews ? 'News' : 'Blogs';
    $listingRoute = $isNews ? 'news.index' : 'blogs.index';
    $detailRoute = $isNews ? 'news.show' : 'blogs.show';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('website.partials.seo', ['defaultTitle' => (isset($post) ? $post->title : $listingTitle).' – '.$siteSettings['site_title'], 'defaultDescription' => isset($post) ? ($post->excerpt ?: Illuminate\Support\Str::limit(strip_tags($post->content), 160)) : ($isNews ? 'Latest exam news, announcements and education updates.' : 'Study tips, exam preparation and learning insights.'), 'socialType' => isset($post) ? 'article' : 'website'])
@if(!isset($post) && $posts->total() === 0)<meta name="robots" content="noindex,follow">@endif

<link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">@include('website.partials.favicon')@include('website.partials.canonical')
@if(isset($post))
@php
    $articleSchema = ['@context' => 'https://schema.org', '@type' => $isNews ? 'NewsArticle' : 'Article', 'headline' => $post->title, 'description' => $post->excerpt ?: Illuminate\Support\Str::limit(strip_tags($post->content), 160), 'datePublished' => $post->published_at->toIso8601String(), 'dateModified' => $post->updated_at->toIso8601String(), 'author' => ['@type' => 'Organization', 'name' => $siteSettings['site_title']], 'mainEntityOfPage' => request()->url()];
    if ($post->thumbnailUrl()) { $articleSchema['image'] = [$post->thumbnailUrl()]; }
    $postBreadcrumb = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')], ['@type' => 'ListItem', 'position' => 2, 'name' => $listingTitle, 'item' => route($listingRoute)], ['@type' => 'ListItem', 'position' => 3, 'name' => $post->title, 'item' => request()->url()]]];
@endphp
<script type="application/ld+json">{!! json_encode([$articleSchema, $postBreadcrumb], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
</head><body class="blog-page">
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name', 'email')) }};</script>@endif
<main @class(['wrap', 'blog-main', 'blog-detail' => isset($post)])>
<div class="crumb"><a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span>@if(isset($post))<a href="{{ route($listingRoute) }}">{{ __($listingTitle) }}</a><span>/</span><b>{{ $post->title }}</b>@else<b>{{ __($listingTitle) }}</b>@endif</div>
@if(isset($post))
<div class="blog-detail-layout">
<article class="blog-article" id="blog-article">
<span class="blog-label">{{ $isNews ? 'NEWS & UPDATES' : 'STUDY & LEARNING' }}</span>
<h1>{{ $post->title }}</h1>
<div class="blog-meta"><span class="blog-author-mark" aria-hidden="true">Q</span><time datetime="{{ $post->published_at->toDateString() }}">📅 {{ $post->published_at->format('d M Y') }}</time><span>⏱ {{ $post->readingMinutes() }} min read</span><span>✍ {{ $siteSettings['site_title'] }}</span></div>
@if($post->excerpt)<p class="blog-lead">{{ $post->excerpt }}</p>@endif
<div class="blog-content">{!! App\PostContent::render($post->content) !!}</div>
<a class="blog-back" href="{{ route($listingRoute) }}">← All {{ strtolower($listingTitle) }}</a>
</article>
<aside class="blog-sidebar" aria-label="Article resources">
    <section class="blog-sidebar-card blog-toc" hidden>
        <h2>Table of contents</h2>
        <nav aria-label="Article sections"><ol id="blog-toc-list"></ol></nav>
    </section>
    <section class="blog-sidebar-card blog-prep-card">
        <span class="blog-label">PREPARE WITH CONFIDENCE</span>
        <h2>Turn learning into practice</h2>
        <p>Build your knowledge with chapter-wise questions and track your preparation.</p>
        <a class="btn btn-o" href="{{ route('learning') }}">Start practising <span aria-hidden="true">→</span></a>
    </section>
    @if($latestBlogs->isNotEmpty())
    <section class="blog-sidebar-card">
        <h2>Latest articles</h2>
        <div class="blog-latest-list">
            @foreach($latestBlogs as $entry)
            <a class="blog-latest-item" href="{{ route($detailRoute, $entry->slug) }}">
                <strong>{{ $entry->title }}</strong>
                <span>{{ $entry->published_at->format('d M Y') }} · {{ $entry->readingMinutes() }} min read</span>
            </a>
            @endforeach
        </div>
        <a class="blog-sidebar-link" href="{{ route($listingRoute) }}">Explore all {{ strtolower($listingTitle) }} <span aria-hidden="true">→</span></a>
    </section>
    @endif
</aside>
</div>
@else
<span class="daily-label">KEEP LEARNING</span><h1>{{ __($listingTitle) }}</h1><p class="sub">{{ $isNews ? 'The latest exam announcements, education news and updates.' : 'Study tips, fresh perspectives and ideas to help you prepare with confidence.' }}</p>
<div class="blog-grid">@forelse($posts as $entry)@include('website.partials.blog-card', ['blog' => $entry])@empty<div class="box" style="padding:32px"><h2>New {{ strtolower($listingTitle) }} {{ $isNews ? 'is' : 'are' }} on the way</h2><p>{{ $isNews ? 'Check back soon for the latest news and announcements.' : 'Check back soon for study tips and learning insights.' }}</p></div>@endforelse</div>
<div style="margin-top:28px">@if($posts->hasPages())<nav aria-label="Pagination" style="display:flex;gap:16px;align-items:center">@if($posts->previousPageUrl())<a class="btn" href="{{ $posts->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>@if($posts->nextPageUrl())<a class="btn" href="{{ $posts->nextPageUrl() }}">Next →</a>@endif</nav>@endif</div>
@endif
</main>
@include('website.partials.footer')@include('website.partials.content-protection')
<script src="{{ asset('js/navigation.js') }}?v={{ filemtime(public_path('js/navigation.js')) }}"></script>
</body></html>
