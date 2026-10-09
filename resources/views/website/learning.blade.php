<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">@include('website.partials.seo', ['defaultTitle' => $title.' – '.$siteSettings['site_title']])<meta name="robots" content="{{ $page === 'leaderboard' ? 'index,follow' : 'noindex,follow' }}"><link rel="stylesheet" href="{{ asset('css/website.css') }}">@include('website.partials.favicon')@include('website.partials.canonical')</head>
<body>
@include('website.partials.header')
<script>window.CURRENT_USER={{ Illuminate\Support\Js::from(auth()->user()?->only('name', 'email')) }};</script>
<main class="wrap daily-page learning-page">
<div class="crumb"><a href="{{ route('home') }}">{{ __('Home') }}</a><span>/</span><a href="{{ route('learning') }}">{{ __('My learning') }}</a><span>/</span><b>{{ $title }}</b></div>
<h1>{{ $title }}</h1>
@if(session('status'))<p class="daily-notice" role="status">{{ session('status') }}</p>@endif
@if($errors->any())<div class="daily-notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if($page === 'dashboard')
<p class="sub">A little practice every day. Save questions, revisit mistakes, and build your confidence.</p>
<div class="daily-stats"><div class="box"><strong>{{ $dashboard['streak'] }} days</strong><span>Current streak · complete your daily target</span></div><div class="box"><strong>{{ $dashboard['todayCount'] }} / {{ $dashboard['target'] }}</strong><span>Distinct questions answered today</span><progress value="{{ min($dashboard['todayCount'], $dashboard['target']) }}" max="{{ $dashboard['target'] }}" aria-label="Daily practice target"></progress></div><div class="box"><strong>{{ $dashboard['wrongCount'] }}</strong><span>Questions to revise · {{ $dashboard['bookmarkCount'] }} bookmarked</span></div></div>
<nav class="learning-nav" aria-label="Learning tools"><a class="btn btn-l" href="{{ route('learning', ['filter' => 'wrong']) }}">{{ __('Mistakes') }}</a><a class="btn btn-l" href="{{ route('learning', ['filter' => 'bookmarked']) }}">{{ __('Bookmarks') }}</a><a class="btn btn-l" href="{{ route('learning.leaderboard') }}">{{ __('Weekly leaderboard') }}</a><a class="btn btn-l" href="{{ route('daily') }}">{{ __('Daily quiz') }}</a></nav>
<div class="learning-grid">
<section class="box learning-panel"><h2>{{ __('Choose your next practice') }}</h2><form method="POST" action="{{ route('learning.create') }}" class="content-form">@csrf
<label>{{ __('Practice type') }}<select name="mode"><option value="revision">{{ __('Revise wrong answers') }}</option><option value="bookmarks">{{ __('Practice bookmarks') }}</option><option value="mock">{{ __('Timed mock test') }}</option><option value="challenge">{{ __('Challenge a friend') }}</option></select></label>
<label>{{ __('Subject') }}<select name="subject_id"><option value="">{{ __('All subjects') }}</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select></label>
<label>{{ __('Questions') }}<select name="count"><option value="10">10 questions</option><option value="20">20 questions</option><option value="50">50 questions</option></select></label><p class="sub">One minute per question. If fewer questions are available, your set uses the available number. Challenges last 7 days and each learner can attempt once.</p><button class="btn btn-o">{{ __('Create practice') }}</button></form></section>
<section class="box learning-panel"><h2>{{ __('Your daily target') }}</h2><form method="POST" action="{{ route('learning.preferences') }}" class="content-form">@csrf<label>{{ __('Questions per day') }}<input name="daily_target" type="number" min="5" max="100" value="{{ $dashboard['target'] }}" required></label><label>{{ __('Preferred explanation language') }}<select name="language"><option value="en" @selected($dashboard['language'] === 'en')>English</option><option value="hi" @selected($dashboard['language'] === 'hi')>हिंदी</option></select></label><label>{{ __('Your exam') }}<select name="exam">@foreach(['general' => 'General practice', 'ssc' => 'SSC', 'upsc' => 'UPSC', 'banking' => 'Banking', 'railways' => 'Railways', 'neet' => 'NEET', 'jee' => 'JEE'] as $exam => $label)<option value="{{ $exam }}" @selected($dashboard['exam'] === $exam)>{{ $label }}</option>@endforeach</select></label><p class="sub">Hindi explanations are AI translations of the English explanation. They are saved after the first translation.</p><button class="btn btn-l">{{ __('Save preferences') }}</button></form></section>
</div>
<section class="box learning-panel"><h2>Subject progress and recommendations</h2>@forelse($dashboard['subjects'] as $subject)<div class="learning-subject"><div><strong>{{ $subject->name }}</strong><p>{{ $subject->correct }} / {{ $subject->total }} correct · {{ round($subject->correct / $subject->total * 100) }}%</p>@if($loop->first)<span class="daily-label">Recommended next: review this subject</span>@endif</div><a class="btn btn-l" href="{{ route('subject', $subject->slug) }}">{{ in_array($subject->slug, ['current-affairs', 'general-knowledge'], true) ? 'View quizzes' : 'Study chapters' }}</a><form method="POST" action="{{ route('learning.create') }}">@csrf<input type="hidden" name="mode" value="mock"><input type="hidden" name="subject_id" value="{{ $subject->id }}"><input type="hidden" name="count" value="10"><button class="btn btn-o">Next 10 questions</button></form></div>@empty<p>Complete some subject quizzes to see your strengths and weak areas.</p><a href="{{ route('daily') }}" class="btn btn-o">Start today's quiz</a>@endforelse</section>
<h2>{{ $filter === 'wrong' ? 'Revise your mistakes' : 'Your bookmarked questions' }}</h2>
@forelse($saved as $row)
@php($question = json_decode($row->question, true))
<article class="box learning-panel"><h3>{{ $question['q'] }}</h3><ol type="A">@foreach($question['o'] as $option)<li>{{ $option }}</li>@endforeach</ol>
@if($row->last_answer !== null)<details><summary>Review your answer</summary><p>Your answer: {{ $row->last_answer < 0 ? 'Skipped' : $question['o'][$row->last_answer] }}</p><p>Correct answer: {{ $question['o'][$question['c']] }}</p><p>{{ $question['explanation'] }}</p></details>@endif
@include('website.partials.learning-tools', ['source' => 'question:'.$row->id, 'canExplain' => $row->last_answer !== null])
@if($filter === 'bookmarked')<div class="learning-tools" data-source="question:{{ $row->id }}"><button type="button" class="btn btn-l" data-bookmark="0">{{ __('Remove bookmark') }}</button><p data-tool-message role="status"></p></div>@endif
</article>
@empty<p class="daily-notice">{{ $filter === 'wrong' ? 'No mistakes to revise yet. Your wrong and skipped answers will appear here after practice.' : 'No saved questions yet. Use Save question while practising.' }}</p>@endforelse
{{ $saved->links() }}
@if($recentSessions->isNotEmpty())<h2>Your recent practice</h2>@foreach($recentSessions as $recent)<p><a href="{{ route('learning.session', $recent->id) }}">{{ $recent->title }} · {{ \Carbon\Carbon::parse($recent->created_at)->format('d M Y') }}</a></p>@endforeach
@endif
@elseif($page === 'session')
<p class="sub">{{ count($questions) }} questions · {{ (int) ceil($practice->duration / 60) }} minutes · one attempt · no negative marking</p>
@if($practice->mode === 'challenge')<section class="box learning-panel"><h2>{{ __('Challenge a friend') }}</h2><label>Share this link<input id="challenge-link" readonly value="{{ route('learning.session', $practice->id) }}"></label><button class="btn btn-l" type="button" data-copy-link>Copy challenge link</button><p data-copy-message role="status"></p><p class="sub">Friends sign in to attempt the same questions. Names and scores of participants are visible on this challenge.</p></section>@endif
@if(! $run)
<form method="POST" action="{{ route('learning.start', $practice->id) }}" class="box learning-panel">@csrf<h2>Ready to begin?</h2><p>The timer starts when you press Start practice. Reloading does not restart it.</p><button class="btn btn-o">Start practice</button></form>
@elseif(! $run->completed_at)
@php($remaining = max(0, \Carbon\Carbon::parse($run->started_at)->timestamp + $practice->duration - now()->timestamp))
<p class="daily-notice learning-timer" role="timer" data-timer="{{ $remaining }}">Time remaining: <span data-time></span></p>
<form method="POST" action="{{ route('learning.submit', $practice->id) }}" id="learning-quiz">@csrf
@foreach($questions as $index => $question)<fieldset class="box daily-question"><legend>{{ $index + 1 }}. {{ $question['q'] }}</legend><input type="hidden" name="answers[{{ $index }}]" value="-1">@foreach($question['o'] as $optionIndex => $option)<label class="daily-option"><input type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}" @checked((string) old('answers.'.$index, '-1') === (string) $optionIndex)><span>{{ chr(65 + $optionIndex) }}</span>{{ $option }}</label>@endforeach
@include('website.partials.learning-tools', ['source' => 'question:'.$question['learning_id'], 'canExplain' => false])
</fieldset>@endforeach
<button class="btn btn-o" type="submit">Submit practice</button></form>
@else
<div class="box learning-panel"><h2>Your result: {{ $run->score }} / {{ count($questions) }}</h2><p>Time used: {{ min($practice->duration, max(0, \Carbon\Carbon::parse($run->completed_at)->timestamp - \Carbon\Carbon::parse($run->started_at)->timestamp)) }} seconds</p><a class="btn btn-o" href="{{ route('learning') }}">Revise mistakes and choose next practice</a></div>
@php($answers = json_decode($run->answers, true))
@foreach($questions as $index => $question)<article class="box learning-panel"><h3>{{ $index + 1 }}. {{ $question['q'] }}</h3><p class="{{ $answers[$index] === $question['c'] ? 'daily-correct' : 'daily-incorrect' }}">Your answer: {{ $answers[$index] < 0 ? 'Skipped' : $question['o'][$answers[$index]] }}</p><p>Correct answer: <strong>{{ $question['o'][$question['c']] }}</strong></p><p>{{ $question['explanation'] }}</p>@include('website.partials.learning-tools', ['source' => 'question:'.$question['learning_id'], 'canExplain' => true])</article>@endforeach
@endif
@if($practice->mode === 'challenge')<section class="box learning-panel"><h2>Challenge scores</h2>@forelse($scores as $score)<p>{{ $loop->iteration }}. {{ $score->name }} · {{ $score->score }} / {{ count($questions) }}</p>@empty<p>Your friend could be the first to finish.</p>@endforelse</section>@endif
@elseif($page === 'leaderboard')
<p class="sub">This week's correct answers, Monday to Sunday in IST. Each question counts once per day. Active student accounts only.</p><form method="GET" class="box learning-panel"><label>Compare within a subject<select name="subject_id"><option value="">{{ __('All subjects') }}</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected((string) request('subject_id') === (string) $subject->id)>{{ $subject->name }}</option>@endforeach</select></label><label>Exam group<select name="exam"><option value="">All exams</option>@foreach(['general' => 'General practice', 'ssc' => 'SSC', 'upsc' => 'UPSC', 'banking' => 'Banking', 'railways' => 'Railways', 'neet' => 'NEET', 'jee' => 'JEE'] as $exam => $label)<option value="{{ $exam }}" @selected(request('exam') === $exam)>{{ $label }}</option>@endforeach</select></label><button class="btn btn-l">Apply filter</button></form>
@forelse($scores as $score)<article class="box learning-panel"><h2>{{ $loop->iteration }}. {{ $score->name }}</h2><p>{{ $score->score }} correct answers · {{ $score->total }} questions answered</p></article>@empty<p class="daily-notice">No scores this week yet. Start practising to appear here.</p>@endforelse
@elseif($page === 'reports')
<a class="btn btn-l" href="{{ route('admin') }}">Back to admin</a>
@forelse($reports as $report)@php($question = json_decode($report->question, true))<article class="box learning-panel"><h2>Report #{{ $report->id }} · {{ $report->status }}</h2><h3>{{ $question['q'] }}</h3><p>Stored correct answer: {{ $question['o'][$question['c']] }}</p><p>{{ $report->reason }}</p><form method="POST" action="{{ route('learning.report.update', $report->id) }}" class="content-form">@csrf<label>Status<select name="status">@foreach(['pending', 'resolved', 'dismissed'] as $status)<option value="{{ $status }}" @selected($report->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label><label>Admin note<textarea name="admin_note" maxlength="2000">{{ $report->admin_note }}</textarea></label><button class="btn btn-o">Save report review</button></form></article>@empty<p>No question reports.</p>@endforelse
{{ $reports->links() }}
@endif
</main>
@include('website.partials.footer')
<script>window.LEARNING_LANGUAGE={{ Illuminate\Support\Js::from($dashboard['language'] ?? 'en') }};</script>
@include('website.partials.learning-scripts')
<script src="{{ asset('js/navigation.js') }}"></script>
</body></html>
