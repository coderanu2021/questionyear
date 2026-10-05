<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Daily quiz – {{ $siteSettings['site_title'] }}</title><meta name="description" content="Take today's daily quiz, earn marks and review your answers. Practice 50 questions as a guest or up to 150 with an account."><link rel="stylesheet" href="{{ asset('css/website.css') }}">@include('website.partials.favicon')
</head>
<body>
@include('website.partials.header')
<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()?->only('name', 'email')) }};</script>
<main class="wrap daily-page">
    <div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span><b>Daily quiz</b></div>
    <section class="daily-hero"><div><span class="daily-label">YOUR DAILY PRACTICE</span><h1>A little practice.<br>A stronger tomorrow.</h1><p>A fresh selection every day. Solve, submit and learn from your answers.</p><span>{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }} · Resets at midnight IST</span></div><div class="daily-orbit" aria-hidden="true"><span>Q</span><b>?</b></div></section>
    <div class="daily-stats"><div class="box"><strong>{{ $maximumSets * 50 }}</strong><span>Daily question allowance</span></div><div class="box"><strong>{{ $attempts->sum('score') }} / {{ $attempts->sum('total') }}</strong><span>Marks earned today</span></div><div class="box"><strong>{{ $attempts->count() }} / {{ $availableSets }}</strong><span>Available sets completed</span></div></div>
    <p class="sub">50 questions per set · 1 mark for each correct answer · No negative marking. @guest <a href="{{ route('login') }}">Sign in</a> to unlock up to 150 daily questions. @endguest</p>
    @if(session('status'))<p class="daily-notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="daily-notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(count($questions))
        <div class="daily-heading"><div><h2>Set {{ $setNumber }} <span class="daily-label">{{ count($questions) }} QUESTIONS</span></h2><p>Choose your answers, then submit to see your score.</p></div><a href="#daily-submit" class="btn btn-l">Go to submit ↓</a></div>
        @if(count($questions) < 50)<p class="daily-notice">This set currently contains {{ count($questions) }} published questions. More questions will appear as they are added.</p>@endif
        <form method="POST" action="{{ route('daily.submit') }}" id="daily-form">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="set_number" value="{{ $setNumber }}">
        @foreach($questions as $index => $question)
            <fieldset class="box daily-question"><legend><span>{{ $index + 1 }}</span> {{ $question['q'] }}</legend>
                <input type="hidden" name="answers[{{ $index }}]" value="-1">
                @foreach($question['o'] as $optionIndex => $option)<label class="daily-option"><input type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}" @checked((string) old('answers.'.$index, '-1') === (string) $optionIndex)><span>{{ chr(65 + $optionIndex) }}</span>{{ $option }}</label>@endforeach
            </fieldset>
        @endforeach
        <div class="box daily-submit" id="daily-submit"><div><h3>Ready to see your marks?</h3><p>Unanswered questions earn 0 marks. You can submit each set once per day.</p></div><button class="btn btn-o" type="submit">Submit quiz →</button></div>
        </form>
    @elseif($availableSets === 0)
        <div class="box daily-empty"><h2>Your daily challenge is on its way</h2><p>There are no published questions yet. Check back after quizzes are added.</p><a href="{{ route('home') }}" class="btn btn-o">Explore subjects</a></div>
    @else
        <div class="box daily-empty"><span class="daily-label">TODAY'S PRACTICE COMPLETE</span><h2>Great work. See you tomorrow!</h2><p>You earned {{ $attempts->sum('score') }} out of {{ $attempts->sum('total') }} marks.</p>@guest<a href="{{ route('register') }}" class="btn btn-o">Create an account for 150 questions</a>@else<a href="{{ route('progress') }}" class="btn btn-o">View my progress</a>@endguest</div>
    @endif
    @if($lastAttempt)
        <details class="box daily-review" @if(session('status')) open @endif><summary>Review set {{ $lastAttempt->set_number }} · {{ $lastAttempt->score }}/{{ $lastAttempt->total }} marks</summary>
        @php($reviewQuestions = json_decode($lastAttempt->questions, true))
        @php($reviewAnswers = json_decode($lastAttempt->answers, true))
        @foreach($reviewQuestions as $index => $question)<article class="daily-review-item"><h3>{{ $index + 1 }}. {{ $question['q'] }}</h3><p class="{{ $reviewAnswers[$index] === $question['c'] ? 'daily-correct' : 'daily-incorrect' }}">Your answer: {{ $reviewAnswers[$index] < 0 ? 'Skipped' : $question['o'][$reviewAnswers[$index]] }}</p><p>Correct answer: <strong>{{ $question['o'][$question['c']] }}</strong></p>@if(!empty($question['explanation']))<p class="sub">{{ $question['explanation'] }}</p>@endif</article>@endforeach
        </details>
    @endif
</main>
@include('website.partials.footer')
@include('website.partials.content-protection')
<script src="{{ asset('js/navigation.js') }}"></script>
<script>document.getElementById('daily-form')?.addEventListener('submit', function () { this.querySelector('button[type="submit"]').disabled = true; });</script>
</body></html>
