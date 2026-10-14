<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('website.partials.seo', ['defaultTitle' => $title.' – '.$siteSettings['site_title'], 'defaultDescription' => $description, 'socialCards' => true])
    <link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">
    @include('website.partials.favicon')
    @include('website.partials.canonical', ['socialCards' => true])
</head>
<body>
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name', 'email')) }};</script>@endif
<main class="wrap notification-page">
    <div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('notifications.index') }}">2027 notifications</a>@if($exam)<span>/</span><b>{{ $exam === 'upsc' ? 'UPSC' : 'Railways' }}</b>@endif</div>
    <span class="daily-label">PLAN YOUR NEXT STEP</span>
    <h1>{{ $title }}</h1>
    <p class="sub">{{ $description }}</p>
    <p class="notification-checked">Last checked: <time datetime="2026-10-14">14 October 2026</time>. Please check the official notification for the latest updates.</p>
    <div class="notification-layout">
        <div class="notification-content">
            @if($exam === null || $exam === 'railways')
                <div class="notification-list">
                    @foreach($notices as $slug => $notice)
                        <a class="card notification-list-card" href="{{ route('notifications.show', ['exam' => $slug]) }}">
                            <span class="daily-label">{{ $notice['status'] }}</span>
                            <h2>{{ $notice['title'] }}</h2>
                            <p>{{ $notice['summary'] }}</p>
                            <span>Read full notice guide →</span>
                        </a>
                    @endforeach
                </div>
            @elseif($exam === 'upsc')
                @include('website.partials.notification-upsc')
            @else
                @include('website.partials.notification-railways')
            @endif
        </div>
        <aside class="box notification-sidebar">
            <h2>Before you apply</h2>
            @if($exam)<a href="{{ route('notifications.index') }}">← All notifications</a>@endif
            <p>Start with the notice number. Then check the post, dates and rules. A calendar gives a plan. The detailed notice gives the full terms.</p>
            <ol><li>Check your date of birth.</li><li>Match your course to the post.</li><li>Read the rules for your category.</li><li>Check skill and medical tests.</li><li>Keep clear copies of your documents.</li></ol>
            <p>A degree alone does not prove you can apply. Age, documents and post rules matter too.</p>
            <a class="btn btn-o" href="{{ route('daily') }}">Practise daily MCQs</a>
            <a href="{{ route('subject', ['subject' => 'history']) }}">Revise History</a>
            <a href="{{ route('subject', ['subject' => 'mathematics']) }}">Practise Maths</a>
        </aside>
    </div>
</main>
@include('website.partials.footer')
<script src="{{ asset('js/navigation.js') }}"></script>
</body>
</html>
