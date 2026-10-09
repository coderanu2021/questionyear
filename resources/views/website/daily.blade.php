<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">@include('website.partials.seo', ['defaultTitle' => ucfirst($period).' quiz'.(request()->route('set') !== null ? ' · '.$date.' · Set '.$setNumber : '').' – '.$siteSettings['site_title'], 'defaultDescription' => 'Practice '.$questionCount.' questions in this '.$period.' quiz'.($practiceSetId ? ' set '.$setNumber.' for '.\Carbon\Carbon::parse($date)->format('d F Y') : '').'. Submit your answers, check your score and review explanations.'])<link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">@include('website.partials.favicon')@include('website.partials.canonical')
@if($archiveSets->total() === 0)<meta name="robots" content="noindex,follow">@endif
</head>
<body>
@include('website.partials.header')
<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()?->only('name', 'email')) }};</script>
<main class="wrap daily-page">
    <div class="crumb"><a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span><b>{{ ucfirst($period) }} quiz</b></div>
    <section class="daily-hero"><div><span class="daily-label">YOUR {{ strtoupper($period) }} PRACTICE</span><h1>{{ __(ucfirst($period).' Practice Quiz') }} @if(request()->route('set') !== null)<br>{{ __('Set :number', ['number' => $setNumber]) }}@endif</h1><p>Solve, submit and learn from your answers. Every saved quiz remains available, including earlier {{ $period === 'daily' ? 'days' : ($period === 'weekly' ? 'weeks' : 'months') }}.</p><span>{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }} · {{ __('Set :number', ['number' => $setNumber]) }} · IST</span></div><div class="daily-orbit" aria-hidden="true"><span>Q</span><b>?</b></div></section>
    <p style="margin-top:20px"><a class="btn btn-l" href="#quiz-archive">Browse all {{ $period }} quizzes ↓</a></p>
    <div class="daily-stats"><div class="box"><strong>{{ $questionCount }}</strong><span>{{ ucfirst($period) }} questions</span></div><div class="box"><strong>{{ $attempts->sum('score') }} / {{ $attempts->sum('total') }}</strong><span>{{ __('Marks earned this period') }}</span></div><div class="box"><strong>{{ $attempts->count() }} / {{ $availableSets }}</strong><span>{{ __('Available sets completed') }}</span></div></div>
    <p class="sub">{{ __(':count questions per set · 1 mark for each correct answer · No negative marking.', ['count' => $questionCount]) }} @guest <a href="{{ route('register') }}">Sign up with email</a> to save your results to your account. @endguest</p>
    @if(session('status'))<p class="daily-notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="daily-notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(count($questions))
        <div class="daily-heading"><div><h2>{{ __('Set :number', ['number' => $setNumber]) }} <span class="daily-label">{{ count($questions) }} QUESTIONS</span></h2><p>{{ __('Choose your answers, then submit to see your score.') }}</p></div><a href="#daily-submit" class="btn btn-l">{{ __('Go to submit ↓') }}</a></div>
        @if(count($questions) < $questionCount)<p class="daily-notice">This set currently contains {{ count($questions) }} published questions. More questions will appear as they are added.</p>@endif
        <form method="POST" action="{{ route($period.'.set.submit', ['set' => $practiceSetId]) }}" id="daily-form">@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="set_number" value="{{ $setNumber }}">
        @foreach($questions as $index => $question)
            <fieldset class="box daily-question"><legend><span>{{ $index + 1 }}</span> {{ $question['q'] }}</legend>
                <input type="hidden" name="answers[{{ $index }}]" value="-1">
                @foreach($question['o'] as $optionIndex => $option)<label class="daily-option"><input type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}" @checked((string) old('answers.'.$index, '-1') === (string) $optionIndex)><span>{{ chr(65 + $optionIndex) }}</span>{{ $option }}</label>@endforeach
                @if($practiceSetId)@include('website.partials.learning-tools', ['source' => 'practice:'.$practiceSetId.':'.$index, 'canExplain' => false])@endif
            </fieldset>
        @endforeach
        <div class="box daily-submit" id="daily-submit"><div><h3>{{ __('Ready to see your marks?') }}</h3><p>Unanswered questions earn 0 marks. You can submit each set once per period.</p></div><button class="btn btn-o" type="submit">{{ __('Submit quiz →') }}</button></div>
        </form>
    @elseif($availableSets === 0)
        <div class="box daily-empty"><h2>Your {{ $period }} challenge is on its way</h2><p>{{ __('Questions for this period are being prepared. Please check back soon.') }}</p><a href="{{ route('home') }}" class="btn btn-o">{{ __('Explore subjects') }}</a></div>
    @else
        <div class="box daily-empty"><span class="daily-label">SET {{ $setNumber }} COMPLETE</span><h2>Great work. Try another saved quiz!</h2><p>You earned {{ $lastAttempt?->score ?? 0 }} out of {{ $lastAttempt?->total ?? 0 }} marks in this set.</p><a href="#quiz-archive" class="btn btn-o">{{ __('Browse saved quizzes') }}</a></div>
    @endif
    <section class="quiz-archive" id="quiz-archive" aria-labelledby="quiz-archive-heading">
        <div class="daily-heading"><div><h2 id="quiz-archive-heading">{{ __('All :period quizzes', ['period' => __($period)]) }}</h2><p>{{ __('Every generated quiz has its own page. Open an earlier set anytime.') }}</p></div><span class="daily-label">{{ $archiveSets->total() }} SAVED SETS</span></div>
        <div class="quiz-archive-grid">
        @foreach($archiveSets as $archiveSet)
            <a class="box quiz-archive-card" href="{{ route($period.'.set', ['set' => $archiveSet->id]) }}" @if($archiveSet->id === $practiceSetId) aria-current="page" @endif>
                <span class="daily-label">{{ ucfirst($period) }} · Set {{ $archiveSet->set_number }}</span>
                <h3>{{ \Carbon\Carbon::parse($archiveSet->starts_on)->format('d M Y') }}</h3>
                <p>{{ $questionCount }} questions · Saved {{ \Carbon\Carbon::parse($archiveSet->created_at)->setTimezone('Asia/Kolkata')->format('d M, H:i') }} IST</p>
                <strong>{{ $archiveSet->id === $practiceSetId ? 'Current quiz' : 'Open quiz' }} →</strong>
            </a>
        @endforeach
        </div>
        @if($archiveSets->hasPages())<nav class="quiz-archive-pagination" aria-label="Quiz archive pages">@if($archiveSets->previousPageUrl())<a class="btn btn-l" href="{{ $archiveSets->previousPageUrl() }}#quiz-archive">← Newer sets</a>@endif<span>Page {{ $archiveSets->currentPage() }} of {{ $archiveSets->lastPage() }}</span>@if($archiveSets->nextPageUrl())<a class="btn btn-l" href="{{ $archiveSets->nextPageUrl() }}#quiz-archive">Older sets →</a>@endif</nav>@endif
    </section>
    @auth<p><a class="btn btn-o" href="{{ route('learning') }}">Revise wrong answers and choose your next 10 questions</a> <a class="btn btn-l" href="{{ route('learning.leaderboard') }}">{{ __('Weekly leaderboard') }}</a></p>@endauth
    @if($lastAttempt)
        <details class="box daily-review" @if(session('status')) open @endif><summary>Review set {{ $lastAttempt->set_number }} · {{ $lastAttempt->score }}/{{ $lastAttempt->total }} marks</summary>
        @php($reviewQuestions = json_decode($lastAttempt->questions, true))
        @php($reviewAnswers = json_decode($lastAttempt->answers, true))
        @foreach($reviewQuestions as $index => $question)<article class="daily-review-item"><h3>{{ $index + 1 }}. {{ $question['q'] }}</h3><p class="{{ $reviewAnswers[$index] === $question['c'] ? 'daily-correct' : 'daily-incorrect' }}">Your answer: {{ $reviewAnswers[$index] < 0 ? 'Skipped' : $question['o'][$reviewAnswers[$index]] }}</p><p>Correct answer: <strong>{{ $question['o'][$question['c']] }}</strong></p>@if(!empty($question['explanation']))<p class="sub">{{ $question['explanation'] }}</p>@endif @if($practiceSetId)@include('website.partials.learning-tools', ['source' => 'practice:'.$practiceSetId.':'.$index, 'canExplain' => true])@endif</article>@endforeach
        </details>
    @endif
</main>
@include('website.partials.footer')
@include('website.partials.content-protection')
<script src="{{ asset('js/navigation.js') }}"></script>
@include('website.partials.learning-scripts')
<script>document.getElementById('daily-form')?.addEventListener('submit', function () { this.querySelector('button[type="submit"]').disabled = true; });</script>
</body></html>
