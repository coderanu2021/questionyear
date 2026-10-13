<!DOCTYPE html>
@php
    $isMonthly = $period === 'monthly';
    $monthLabel = \Carbon\Carbon::parse($date)->format('F Y');
    $publishedQuestionCount = count($questions) ?: ($lastAttempt?->total ?? 0);
    $monthlySetSuffix = $setNumber > 1 ? ' · Set '.$setNumber : '';
    $monthlyTitle = $publishedQuestionCount
        ? $monthLabel.' Monthly GK Quiz | '.$publishedQuestionCount.' MCQs with Answers'.$monthlySetSuffix
        : $monthLabel.' Monthly GK Quiz | Coming Soon';
    $monthlyDescription = $publishedQuestionCount
        ? 'Practice '.$publishedQuestionCount.' important GK, GS and Current Affairs MCQs for '.$monthLabel.'. Prepare for SSC, Railway, Banking and other competitive exams.'
        : 'The '.$monthLabel.' monthly GK, GS and Current Affairs quiz is being prepared. Browse earlier monthly quizzes to keep practicing.';
    $monthlyDraftKey = 'questionyear:monthly:'.$date.':'.$practiceSetId.':'.hash('sha256', (string) (auth()->id() ?? session('daily_guest_id')));
@endphp
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">@include('website.partials.seo', ['defaultTitle' => $isMonthly ? $monthlyTitle : ucfirst($period).' MCQ'.(request()->route('set') !== null ? ' · '.$date.' · Set '.$setNumber : '').' – '.$siteSettings['site_title'], 'defaultDescription' => $isMonthly ? $monthlyDescription : 'Practice '.$questionCount.' questions in this '.$period.' MCQ'.($practiceSetId ? ' set '.$setNumber.' for '.\Carbon\Carbon::parse($date)->format('d F Y') : '').'. Submit your answers, check your score and review explanations.', 'preserveQuizWording' => $isMonthly, 'socialCards' => $isMonthly])<link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">@include('website.partials.favicon')@include('website.partials.canonical', ['canonicalPath' => $isMonthly && $practiceSetId ? parse_url(route('monthly.set', ['set' => $practiceSetId]), PHP_URL_PATH) : request()->getPathInfo(), 'socialCards' => $isMonthly])
@if($isMonthly)
<link rel="stylesheet" href="{{ asset('css/monthly-quiz.css') }}?v={{ filemtime(public_path('css/monthly-quiz.css')) }}">
<script src="{{ asset('js/monthly-quiz.js') }}?v={{ filemtime(public_path('js/monthly-quiz.js')) }}" defer></script>
@endif
@if($archiveSets->total() === 0 || ($isMonthly && ! $publishedQuestionCount))<meta name="robots" content="noindex,follow">@endif
</head>
<body>
@include('website.partials.header')
<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()?->only('name', 'email')) }};</script>
<main class="wrap daily-page {{ $isMonthly ? 'monthly-page' : '' }}" @if($isMonthly && $lastAttempt) data-completed-draft="{{ $monthlyDraftKey }}" @endif>
    <div class="crumb"><a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span><b>{{ ucfirst($period) }} MCQ</b></div>
    <section class="daily-hero"><div><span class="daily-label">YOUR {{ strtoupper($period) }} PRACTICE</span>
        @if($isMonthly)
        <h1>{{ $monthLabel }} Monthly Quiz{{ $publishedQuestionCount ? ' – '.$publishedQuestionCount.' Important MCQs' : ' – Coming Soon' }}{{ $monthlySetSuffix }}</h1>
        <p>{{ $publishedQuestionCount ? 'Practice '.$publishedQuestionCount.' GK, GS and Current Affairs questions for '.$monthLabel.'. Build your preparation for SSC, Railway, Banking and other competitive exams, then review your score and answer explanations.' : $monthlyDescription }}</p>
        @else
        <h1>{{ __(ucfirst($period).' Practice MCQ') }} @if(request()->route('set') !== null)<br>{{ __('Set :number', ['number' => $setNumber]) }}@endif</h1><p>Solve, submit and learn from your answers. Every saved MCQ remains available, including earlier {{ $period === 'daily' ? 'days' : 'weeks' }}.</p>
        @endif
        <span>{{ \Carbon\Carbon::parse($date)->format('l, d F Y') }} · {{ __('Set :number', ['number' => $setNumber]) }} · IST</span></div><div class="daily-orbit" aria-hidden="true"><span>Q</span><b>?</b></div></section>
    <p style="margin-top:20px"><a class="btn btn-l" href="#quiz-archive">Browse all {{ $period }} MCQs ↓</a></p>
    <div class="daily-stats"><div class="box"><strong>{{ $questionCount }}</strong><span>{{ ucfirst($period) }} questions</span></div><div class="box"><strong>{{ $attempts->sum('score') }} / {{ $attempts->sum('total') }}</strong><span>{{ __('Marks earned this period') }}</span></div><div class="box"><strong>{{ $attempts->count() }} / {{ $availableSets }}</strong><span>{{ __('Available sets completed') }}</span></div></div>
    <p class="sub">{{ __(':count questions per set · 1 mark for each correct answer · No negative marking.', ['count' => $questionCount]) }} @guest <a href="{{ route('register') }}">Sign up with email</a> to save your results to your account. @endguest</p>
    @if(session('status'))<p class="daily-notice" role="status">{{ session('status') }}</p>@endif
    @if($errors->any())<div class="daily-notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if(count($questions))
        <div class="daily-heading"><div><h2>{{ __('Set :number', ['number' => $setNumber]) }} <span class="daily-label">{{ count($questions) }} QUESTIONS</span></h2><p>{{ __('Choose your answers, then submit to see your score.') }}</p></div><a href="#daily-submit" class="btn btn-l">{{ __('Go to submit ↓') }}</a></div>
        @if(count($questions) < $questionCount)<p class="daily-notice">This set currently contains {{ count($questions) }} published questions. More questions will appear as they are added.</p>@endif
        <form method="POST" action="{{ route($period.'.set.submit', ['set' => $practiceSetId]) }}" id="daily-form" @if($isMonthly) data-monthly-quiz data-draft-key="{{ $monthlyDraftKey }}" data-question-signature="{{ hash('sha256', json_encode(array_map(fn ($question) => [$question['q'], $question['o']], $questions))) }}" data-has-old-answers="{{ old('answers') !== null ? 'true' : 'false' }}" @endif>@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="set_number" value="{{ $setNumber }}">
        @if($isMonthly)
        <section class="monthly-progress box" aria-label="Quiz progress">
            <div class="monthly-progress-copy"><strong data-range tabindex="-1">{{ count($questions) }} questions in one monthly quiz</strong><span data-answered>Choose an answer to begin</span></div>
            <progress data-progress value="0" max="{{ count($questions) }}" aria-label="Questions answered"></progress>
            <p class="monthly-status" data-draft-status role="status">Your answers stay selected while you browse question batches.</p>
        </section>
        <details class="monthly-navigation box" data-monthly-controls hidden>
            <summary>Question navigator <span>Answered / unanswered</span></summary>
            <p class="monthly-nav-legend"><span><i class="monthly-dot is-answered" aria-hidden="true"></i> Answered</span><span><i class="monthly-dot" aria-hidden="true"></i> Unanswered</span></p>
            <div class="monthly-question-grid" aria-label="Jump to a question">
                @foreach($questions as $index => $question)<button type="button" data-question-target="{{ $index }}" aria-label="Question {{ $index + 1 }}, unanswered">{{ $index + 1 }}</button>@endforeach
            </div>
        </details>
        <p class="monthly-announcement" data-page-status role="status" aria-live="polite"></p>
        @endif
        @foreach($questions as $index => $question)
            @if($isMonthly && $index % 20 === 0)<div class="monthly-batch" data-question-batch>@endif
            <fieldset class="box daily-question" @if($isMonthly) id="monthly-question-{{ $index + 1 }}" data-question-index="{{ $index }}" tabindex="-1" @endif><legend><span>{{ $index + 1 }}</span> {{ $question['q'] }}</legend>
                <input type="hidden" name="answers[{{ $index }}]" value="-1">
                @foreach($question['o'] as $optionIndex => $option)<label class="daily-option"><input type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}" @checked((string) old('answers.'.$index, '-1') === (string) $optionIndex)><span>{{ chr(65 + $optionIndex) }}</span>{{ $option }}</label>@endforeach
                @if($practiceSetId)@include('website.partials.learning-tools', ['source' => 'practice:'.$practiceSetId.':'.$index, 'canExplain' => false])@endif
            </fieldset>
            @if($isMonthly && (($index + 1) % 20 === 0 || $loop->last))</div>@endif
        @endforeach
        @if($isMonthly)
        <nav class="monthly-pagination" aria-label="Question batches" data-monthly-controls hidden>
            <button type="button" class="btn btn-l" data-previous>← Previous</button><span data-page-number></span><button type="button" class="btn btn-o" data-next>Next →</button>
        </nav>
        @endif
        <div class="box daily-submit" id="daily-submit"><div><h3>{{ __('Ready to see your marks?') }}</h3><p>Unanswered questions earn 0 marks. You can submit each set once per period.</p></div><button class="btn btn-o" type="submit">{{ __('Submit MCQ →') }}</button></div>
        </form>
    @elseif($availableSets === 0)
        <div class="box daily-empty"><h2>Your {{ $period }} challenge is on its way</h2><p>{{ __('Questions for this period are being prepared. Please check back soon.') }}</p><a href="{{ route('home') }}" class="btn btn-o">{{ __('Explore subjects') }}</a></div>
    @else
        <div class="box daily-empty"><span class="daily-label">SET {{ $setNumber }} COMPLETE</span><h2>Great work. Try another saved MCQ!</h2><p>You earned {{ $lastAttempt?->score ?? 0 }} out of {{ $lastAttempt?->total ?? 0 }} marks in this set.</p>@if($isMonthly)<a href="#monthly-review" class="btn btn-o">Review answers and explanations ↓</a> @endif<a href="#quiz-archive" class="btn btn-l">{{ __('Browse saved MCQs') }}</a></div>
    @endif
    <section class="quiz-archive" id="quiz-archive" aria-labelledby="quiz-archive-heading">
        <div class="daily-heading"><div><h2 id="quiz-archive-heading">{{ __('All :period MCQs', ['period' => __($period)]) }}</h2><p>{{ __('Every generated MCQ has its own page. Open an earlier set anytime.') }}</p></div><span class="daily-label">{{ $archiveSets->total() }} SAVED SETS</span></div>
        <div class="quiz-archive-grid">
        @foreach($archiveSets as $archiveSet)
            <a class="box quiz-archive-card" href="{{ route($period.'.set', ['set' => $archiveSet->id]) }}" @if($archiveSet->id === $practiceSetId) aria-current="page" @endif>
                <span class="daily-label">{{ ucfirst($period) }} · Set {{ $archiveSet->set_number }}</span>
                <h3>{{ \Carbon\Carbon::parse($archiveSet->starts_on)->format($isMonthly ? 'F Y' : 'd M Y') }}</h3>
                <p>{{ $questionCount }} questions · Saved {{ \Carbon\Carbon::parse($archiveSet->created_at)->setTimezone('Asia/Kolkata')->format('d M, H:i') }} IST</p>
                <strong>{{ $archiveSet->id === $practiceSetId ? 'Current MCQ' : 'Open MCQ' }} →</strong>
            </a>
        @endforeach
        </div>
        @if($archiveSets->hasPages())<nav class="quiz-archive-pagination" aria-label="MCQ archive pages">@if($archiveSets->previousPageUrl())<a class="btn btn-l" href="{{ $archiveSets->previousPageUrl() }}#quiz-archive">← Newer sets</a>@endif<span>Page {{ $archiveSets->currentPage() }} of {{ $archiveSets->lastPage() }}</span>@if($archiveSets->nextPageUrl())<a class="btn btn-l" href="{{ $archiveSets->nextPageUrl() }}#quiz-archive">Older sets →</a>@endif</nav>@endif
    </section>
    @auth<p><a class="btn btn-o" href="{{ route('learning') }}">Revise wrong answers and choose your next 10 questions</a> <a class="btn btn-l" href="{{ route('learning.leaderboard') }}">{{ __('Weekly leaderboard') }}</a></p>@endauth
    @if($lastAttempt)
        <details class="box daily-review" @if($isMonthly) id="monthly-review" @endif @if(session('status') || $isMonthly) open @endif><summary>Review set {{ $lastAttempt->set_number }} · {{ $lastAttempt->score }}/{{ $lastAttempt->total }} marks</summary>
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
@unless($isMonthly)<script>document.getElementById('daily-form')?.addEventListener('submit', function () { this.querySelector('button[type="submit"]').disabled = true; });</script>@endunless
</body></html>
