<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $title }} – {{ $siteSettings['site_title'] }}</title><link rel="stylesheet" href="{{ asset('css/website.css') }}"></head><body>
@include('website.partials.header')
@if(auth()->check())<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()->only('name','email')) }};</script>@endif
<main class="wrap" style="min-height:60vh;padding-top:48px;padding-bottom:64px"><div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span><b>{{ $title }}</b></div><h1>{{ $title }}</h1><p class="sub" style="white-space:pre-line">{{ $description }}</p>
@if(session('status'))<p class="box" role="status" style="padding:20px">{{ session('status') }}</p>@endif
@if($errors->any())<div class="box" role="alert" style="padding:20px">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($page === 'contact')
<p class="site-contact-line">Email us at <a href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a></p>
<form class="box content-form" method="POST" action="{{ route('contact.send') }}">@csrf<label>Your name<input name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="100"></label><label>Email<input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required></label><label>Message<textarea name="message" required minlength="10" maxlength="5000" rows="6">{{ old('message') }}</textarea></label><button class="btn btn-o">Send message</button></form>
@elseif($page === 'forgot-password')
<form class="box content-form" method="POST" action="{{ route('password.email') }}">@csrf<label>Email<input type="email" name="email" value="{{ old('email') }}" required></label><button class="btn btn-o">Send reset link</button></form>
@elseif($page === 'reset-password')
<form class="box content-form" method="POST" action="{{ route('password.update') }}">@csrf<input type="hidden" name="token" value="{{ $token }}"><label>Email<input type="email" name="email" value="{{ old('email', request('email')) }}" required></label><label>New password<input type="password" name="password" required minlength="8"></label><label>Confirm password<input type="password" name="password_confirmation" required minlength="8"></label><button class="btn btn-o">Reset password</button></form>
@elseif($page === 'progress')
<div class="grid"><div class="box" style="padding:24px"><h2>{{ $attempts->count() }}</h2><p>Completed quizzes</p></div><div class="box" style="padding:24px"><h2>{{ round($attempts->avg('percentage') ?? 0) }}%</h2><p>Average score</p></div></div><h2 style="margin-top:32px">Recent attempts</h2>
@forelse($attempts as $attempt)<div class="box" style="padding:20px;margin-top:12px"><h3>{{ $quizzes[$attempt->quiz_id]->title ?? 'Quiz' }}</h3><p>{{ $attempt->score }}/{{ $attempt->total }} · {{ $attempt->percentage }}% · {{ $attempt->created_at->format('d M Y H:i') }}</p></div>@empty<p>No attempts yet. <a href="/#popular">Start a quiz</a>.</p>@endforelse
<h2 style="margin-top:32px">Daily quiz results</h2>@forelse($dailyAttempts as $dailyAttempt)<div class="box" style="padding:20px;margin-top:12px"><h3>Daily quiz · {{ $dailyAttempt->quiz_date }} · Set {{ $dailyAttempt->set_number }}</h3><p>{{ $dailyAttempt->score }}/{{ $dailyAttempt->total }} marks</p></div>@empty<p>No daily quiz results yet. <a href="{{ route('daily') }}">Try today's quiz</a>.</p>@endforelse
@elseif($page === 'leaderboard')
@forelse($leaders as $leader)<div class="box" style="padding:20px;margin-top:12px"><h3>{{ $loop->iteration }}. {{ $leader->name }}</h3><p>{{ round($leader->average) }}% average · {{ $leader->attempts }} quizzes</p></div>@empty<p>No results yet. Complete a quiz while logged in to appear here.</p>@endforelse
@elseif($page === 'exam')
<div class="grid">@foreach($subjects as $subject)<a class="card" href="{{ route('subject', $subject->slug) }}"><h3>{{ $subject->name }}</h3><p>{{ $subject->description }}</p></a>@endforeach</div>
@else
<a class="btn btn-o" href="/#subjects">Explore subjects</a><a class="btn btn-l" href="/contact">Contact us</a>
@endif
</main>
@include('website.partials.footer')
@include('website.partials.content-protection')
<script src="{{ asset('js/navigation.js') }}"></script></body></html>
