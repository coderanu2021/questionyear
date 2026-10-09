<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('website.partials.seo', ['defaultTitle' => (isset($post) ? $post->title : 'Blogs').' – '.$siteSettings['site_title'], 'defaultDescription' => isset($post) ? ($post->excerpt ?: Illuminate\Support\Str::limit($post->content, 160)) : 'Study tips, exam preparation and learning insights.'])
@if(!isset($post) && $posts->total() === 0)<meta name="robots" content="noindex,follow">@endif

<link rel="stylesheet" href="{{ asset('css/website.css') }}">@include('website.partials.favicon')@include('website.partials.canonical')
</head><body>
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name', 'email')) }};</script>@endif
<main class="wrap" style="min-height:60vh;padding-top:48px;padding-bottom:64px">
<div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span>@if(isset($post))<a href="{{ route('blogs.index') }}">Blogs</a><span>/</span><b>{{ $post->title }}</b>@else<b>Blogs</b>@endif</div>
@if(isset($post))
<article style="max-width:800px;margin:0 auto"><span class="daily-label">STUDY &amp; LEARNING</span><h1>{{ $post->title }}</h1><p class="sub"><time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->format('d M Y') }}</time></p>@if($post->excerpt)<p class="sub">{{ $post->excerpt }}</p>@endif<div class="box" style="padding:clamp(20px,4vw,40px);white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.9">{{ $post->content }}</div><p style="margin-top:24px"><a class="btn btn-l" href="{{ route('blogs.index') }}">← All blogs</a></p></article>
@else
<span class="daily-label">KEEP LEARNING</span><h1>Blogs</h1><p class="sub">Study tips, fresh perspectives and ideas to help you prepare with confidence.</p>
<div class="grid">@forelse($posts as $entry)<article class="box" style="padding:28px;display:flex;flex-direction:column;gap:16px;overflow-wrap:anywhere"><span class="daily-label">STUDY &amp; LEARNING</span><h2 style="font-size:23px"><a href="{{ route('blogs.show', $entry->slug) }}">{{ $entry->title }}</a></h2><time datetime="{{ $entry->published_at->toDateString() }}">{{ $entry->published_at->format('d M Y') }}</time><p>{{ $entry->excerpt ?: Illuminate\Support\Str::limit($entry->content, 180) }}</p><a class="btn btn-l" style="align-self:flex-start;margin-top:auto" href="{{ route('blogs.show', $entry->slug) }}">Read blog →</a></article>@empty<div class="box" style="padding:32px"><h2>New blogs are on the way</h2><p>Check back soon for study tips and learning insights.</p></div>@endforelse</div>
<div style="margin-top:28px">@if($posts->hasPages())<nav aria-label="Pagination" style="display:flex;gap:16px;align-items:center">@if($posts->previousPageUrl())<a class="btn" href="{{ $posts->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $posts->currentPage() }} of {{ $posts->lastPage() }}</span>@if($posts->nextPageUrl())<a class="btn" href="{{ $posts->nextPageUrl() }}">Next →</a>@endif</nav>@endif</div>
@endif
</main>
@include('website.partials.footer')@include('website.partials.content-protection')
<script src="{{ asset('js/navigation.js') }}"></script>
</body></html>
