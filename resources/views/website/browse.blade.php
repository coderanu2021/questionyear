<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
@include('website.partials.seo', ['defaultTitle' => $title.' – '.$siteSettings['site_title'], 'defaultDescription' => $description])
@include('website.partials.canonical')
@php
    $browseBreadcrumbs = [['name' => 'Home', 'url' => route('home')], ...($parent ? [['name' => $parent['label'], 'url' => $parent['url']]] : []), ['name' => $title, 'url' => request()->url()]];
@endphp
@include('website.partials.breadcrumb-schema', ['items' => $browseBreadcrumbs])
@if(collect($sections)->every(fn ($section) => empty($section['links'])))<meta name="robots" content="noindex,follow">@endif
<link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">@include('website.partials.favicon')
</head><body>
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name','email')) }};</script>@endif
<main class="wrap browse-page">
<div class="crumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span>/</span>@if($parent)<a href="{{ $parent['url'] }}">{{ $parent['label'] }}</a><span>/</span>@endif<b aria-current="page">{{ $title }}</b></div>
<h1>{{ $title }}</h1><p class="sub">{{ $description }}</p>
@if(request()->routeIs('exam'))<div class="browse-links"><a class="btn btn-o" href="{{ route('practice.index', ['mode' => 'mock']) }}">Create a mock test</a><a class="btn btn-l" href="{{ route('current-affairs') }}">Current Affairs</a></div>@endif
@foreach($sections as $section)
<section class="browse-section"><h2>{{ $section['title'] }}</h2>@if($section['description'] ?? null)<p>{{ $section['description'] }}</p>@endif
<div class="browse-grid">@forelse($section['links'] as $link)<a class="card" href="{{ $link['url'] }}"><h3>{{ $link['label'] }}</h3>@if($link['description'] ?? null)<p>{{ $link['description'] }}</p>@endif<span>Explore →</span></a>@empty<p class="browse-empty">No published resources in this section yet.</p>@endforelse</div>
</section>
@endforeach
</main>
@include('website.partials.footer')
<script src="{{ asset('js/navigation.js') }}?v={{ filemtime(public_path('js/navigation.js')) }}"></script>
</body></html>
