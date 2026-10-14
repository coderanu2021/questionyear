<!DOCTYPE html>
@php
    $examLabels = ['ssc' => 'SSC', 'banking' => 'Banking', 'upsc' => 'UPSC', 'railways' => 'Railway'];
    $requestedExam = request()->query('exam');
    $examLabel = is_string($requestedExam) ? ($examLabels[$requestedExam] ?? null) : null;
    $upcomingTitle = ($examLabel ? $examLabel.' practice coming soon' : 'More exam practice coming soon').' – '.$siteSettings['site_title'];
    $upcomingDescription = $examLabel
        ? $examLabel.' practice sets are being prepared on '.$siteSettings['site_title'].'. Try the daily MCQ while you wait for dedicated '.$examLabel.' practice.'
        : 'Dedicated exam practice sets are being prepared on '.$siteSettings['site_title'].'. Keep revising with daily MCQs and available subject notes.';
@endphp
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">@include('website.partials.seo', ['defaultTitle' => $upcomingTitle, 'defaultDescription' => $upcomingDescription])<meta name="robots" content="noindex,follow"><link rel="stylesheet" href="{{ asset('css/website.css') }}">@include('website.partials.favicon')@include('website.partials.canonical')
</head><body>
@include('website.partials.header')
<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()?->only('name', 'email')) }};</script>
<main class="wrap destination-page"><div class="destination-art" aria-hidden="true"><svg viewBox="0 0 240 180" width="240"><circle cx="120" cy="90" r="78" fill="currentColor" opacity=".08"/><rect x="55" y="38" width="130" height="115" rx="15" fill="var(--card)" stroke="currentColor" stroke-width="3"/><path d="M55 70h130M88 26v28M152 26v28" fill="none" stroke="currentColor" stroke-width="5" stroke-linecap="round"/><path d="m93 113 18 18 37-40" fill="none" stroke="currentColor" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg></div><span class="daily-label">SOMETHING GOOD IS ON THE WAY</span><h1>Your next challenge is coming soon.</h1><p>We're preparing SSC, UPSC, Banking and Railway practice.<br>Keep your momentum going with today's daily MCQ.</p><div class="destination-actions"><a class="btn btn-o" href="{{ route('daily') }}">Try the daily MCQ →</a><a class="btn btn-l" href="{{ route('home') }}">Back to home</a></div><div class="exam-pills"><span>SSC</span><span>UPSC</span><span>Banking</span><span>Railway</span></div></main>
@include('website.partials.footer')
<script src="{{ asset('js/navigation.js') }}"></script></body></html>
