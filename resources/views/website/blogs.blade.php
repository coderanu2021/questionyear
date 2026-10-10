<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('website.partials.seo', ['defaultTitle' => (isset($post) ? $post->title : 'Blogs').' – '.$siteSettings['site_title'], 'defaultDescription' => isset($post) ? ($post->excerpt ?: Illuminate\Support\Str::limit(strip_tags($post->content), 160)) : 'Study tips, exam preparation and learning insights.'])
@if(!isset($post) && $posts->total() === 0)<meta name="robots" content="noindex,follow">@endif

<link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">@include('website.partials.favicon')@include('website.partials.canonical')
</head><body class="blog-page">
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name', 'email')) }};</script>@endif
<main @class(['wrap', 'blog-main', 'blog-detail' => isset($post)])>
<div class="crumb"><a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span>@if(isset($post))<a href="{{ route('blogs.index') }}">{{ __('Blogs') }}</a><span>/</span><b>{{ $post->title }}</b>@else<b>{{ __('Blogs') }}</b>@endif</div>
@if(isset($post))
<article class="blog-article">
<span class="blog-label">STUDY &amp; LEARNING</span>
<h1>{{ $post->title }}</h1>
<div class="blog-meta"><time datetime="{{ $post->published_at->toDateString() }}">📅 {{ $post->published_at->format('d M Y') }}</time><span>⏱ {{ $post->readingMinutes() }} min read</span><span>✍ {{ $siteSettings['site_title'] }}</span></div>
@if($post->excerpt)<p class="blog-lead">{{ $post->excerpt }}</p>@endif
<div class="blog-content">{!! App\PostContent::render($post->content) !!}</div>
<a class="blog-back" href="{{ route('blogs.index') }}">← All blogs</a>
</article>
@else
<span class="daily-label">KEEP LEARNING</span><h1>{{ __('Blogs') }}</h1><p class="sub">Study tips, fresh perspectives and ideas to help you prepare with confidence.</p>
<div class="blog-grid">@forelse($posts as $entry)@include('website.partials.blog-card', ['blog' => $entry])@empty<div class="box" style="padding:32px"><h2>New blogs are on the way</h2><p>Check back soon for study tips and learning insights.</p></div>@endforelse</div>
<div style="margin-top:28px">@if($posts->hasPages())<nav aria-label="Pagination" style="display:flex;gap:16px;align-items:center">@if($posts->previousPageUrl())<a class="btn" href="{{ $posts->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>@if($posts->nextPageUrl())<a class="btn" href="{{ $posts->nextPageUrl() }}">Next →</a>@endif</nav>@endif</div>
@endif
</main>
@include('website.partials.footer')@include('website.partials.content-protection')
<script src="{{ asset('js/navigation.js') }}"></script>
</body></html>
