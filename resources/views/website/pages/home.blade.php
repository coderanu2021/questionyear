<main id="home" @if(!request()->routeIs('home')) hidden @endif>
<div class="hero"><div class="wrap">
  <div>
    @if(request()->routeIs('home'))
    <h1>{{ __($siteSettings['home_title']) }}</h1>
    @else
    <h2>{{ __($siteSettings['home_title']) }}</h2>
    @endif
    <p>{{ __($siteSettings['home_description']) }}</p>
    <div class="hs"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg><input id="q" type="search" placeholder="{{ __('Search a subject, e.g. History') }}" aria-label="{{ __('Search subjects') }}"><a href="#subjects" class="btn btn-o">{{ __('Search') }}</a></div>
    <div class="pop"><span>Popular:</span><a href="#subjects" data-s="history">{{ __('History') }}</a><a href="#subjects" data-s="geography">{{ __('Geography') }}</a><a href="#subjects" data-s="biology">Biology</a><a href="#subjects" data-s="mathematics">{{ __('Mathematics') }}</a></div>
  </div>
  <div class="demo" aria-live="polite">
    <div class="demo-h"><span id="dq">Question 1 of 3</span><span id="dsub">{{ __('History') }}</span></div>
    <h3 id="dt"></h3>
    <div id="do"></div>
    <div class="fb" id="fb"></div>
    <div class="demo-f"><span id="ds" style="font-size:14px;color:var(--muted)">Score: 0</span><button id="dn" hidden>{{ __('Next question') }}</button></div>
  </div>
</div></div>

<div class="wrap strip"><div class="box">
  <div><strong data-n="{{ $chapterCount }}">0</strong><span>{{ __('Chapters') }}</span></div>
  <div><strong data-n="{{ $subjectCount }}">0</strong><span>{{ __('Subjects') }}</span></div>
  <div><strong data-n="{{ count($popular) }}">0</strong><span>{{ __('Quizzes ready') }}</span></div>
  <div><strong data-n="{{ $questionCount }}">0</strong><span>{{ __('Practice questions') }}</span></div>
</div></div>

<section id="subjects"><div class="wrap">
  <h2>{{ __('Choose a subject') }}</h2>
  <p class="sub">Pick a subject to start a quiz. Every subject has topic-wise sets, timed tests and previous-style questions.</p>
  <div class="filters" id="fl"></div>
  <div class="grid" id="gr">@foreach($curriculum['S'] as $entry)<a class="card" href="{{ route('subject', Illuminate\Support\Str::slug(str_replace('&', '', $entry[0]))) }}"><div class="card-h"><div><small>{{ $entry[1] }}</small><h3>{{ $entry[0] }}</h3></div></div><p>{{ $entry[2] }}</p><div class="card-f"><span>{{ count($entry[3]) }} topics</span><b>Explore {{ $entry[0] }} →</b></div></a>@endforeach</div>
  <p class="empty" id="em" @if(count($curriculum['S'])) style="display:none" @endif>{{ __('No subjects match your search. Try a different word.') }}</p>
</div></section>

<section class="alt"><div class="wrap dqb">
  <div><span class="pill">{{ __('Practice quizzes') }}</span><h2>{{ __('Today\'s practice challenge') }}</h2><p class="sub">Practice 20 daily, 50 weekly, or 200 monthly questions across subjects. Sign up with email to save your scores and review your answers.</p><a href="{{ route('daily') }}" class="btn btn-o" style="height:44px;padding:0 24px">{{ __('Start today\'s quiz') }}</a> <a href="{{ route('weekly') }}" class="btn btn-l">{{ __('Weekly quiz') }}</a> <a href="{{ route('monthly') }}" class="btn btn-l">{{ __('Monthly quiz') }}</a></div>
  <div class="dqd"><small id="dw">Today</small><b id="dd">1</b><span id="dm"></span></div>
</div></section>

<section id="popular"><div class="wrap"><h2>{{ __('Popular quizzes') }}</h2><p class="sub">{{ __('Published quizzes, ready to practice.') }}</p><div class="g4">@forelse(array_slice($popular,0,8) as $test)<a class="card q" href="{{ $test['url'] }}" style="--c:#0078d4"><h3>{{ $test['title'] }}</h3><p>{{ $test['count'] }} {{ ($test['question_answers'] ?? false) ? 'questions and answers' : 'questions' }}@unless($test['question_answers'] ?? false) · {{ $test['duration'] }} minutes @endunless</p><div class="card-f"><span>@if($test['question_answers'] ?? false){{ $test['date_label'] }}@else Pass {{ $test['pass'] }}% @endif</span><b>{{ ($test['question_answers'] ?? false) ? 'Read answers →' : 'Start →' }}</b></div></a>@empty<p>No quizzes published yet.</p>@endforelse</div></div></section>

<section class="alt" id="exams"><div class="wrap"><h2>{{ __('Practice by exam') }}</h2><p class="sub">{{ __('Find question sets built for the exam you are preparing for.') }}</p><div class="g4"><a href="/exams/upsc" class="card ex"><div><h3>UPSC</h3><p>Prelims and CSAT</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/ssc" class="card ex"><div><h3>SSC</h3><p>CGL, CHSL and MTS</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/banking" class="card ex"><div><h3>Banking</h3><p>IBPS, SBI and RBI</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/railways" class="card ex"><div><h3>Railways</h3><p>RRB NTPC and Group D</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/neet" class="card ex"><div><h3>NEET</h3><p>Biology, Physics, Chemistry</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/jee" class="card ex"><div><h3>JEE</h3><p>Maths, Physics, Chemistry</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/cbse-board" class="card ex"><div><h3>CBSE Board</h3><p>Class 9 to 12</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a><a href="/exams/state-psc" class="card ex"><div><h3>State PSC</h3><p>State-level exams</p></div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg></a></div></div></section>

<section><div class="wrap"><h2>Why practice on {{ $siteSettings['site_title'] }}</h2><p class="sub">Everything you need to learn from your mistakes and improve every week.</p><div class="g3"><div class="feat"><div class="fi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg></div><h3>{{ __('Instant explanations') }}</h3><p>See the correct answer and why, right after each question.</p></div><div class="feat"><div class="fi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div><h3>{{ __('Timed mock tests') }}</h3><p>Practice under real exam time pressure and get a full report.</p></div><div class="feat"><div class="fi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20V10M18 20V4M6 20v-4"/></svg></div><h3>{{ __('Progress tracking') }}</h3><p>Spot weak topics with subject-wise scores and daily streaks.</p></div><div class="feat"><div class="fi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></div><h3>{{ __('Review your answers') }}</h3><p>Review every answer and revisit chapter notes after each quiz.</p></div><div class="feat"><div class="fi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg></div><h3>{{ __('Topic-wise sets') }}</h3><p>Study one chapter at a time or mix topics together.</p></div><div class="feat"><div class="fi"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><path d="M12 18h.01"/></svg></div><h3>Works on any device</h3><p>Practice on phone, tablet or laptop without installing anything.</p></div></div></div></section>

<section class="alt" id="how"><div class="wrap">
  <h2>How it works</h2>
  <p class="sub">From first question to full mock test in four steps.</p>
  <div class="steps">
    <div><h3>Pick a subject</h3><p>Choose from history, geography, science and other subjects.</p></div>
    <div><h3>Learn the chapter</h3><p>Read short notes and key points for each topic.</p></div>
    <div><h3>Answer and learn</h3><p>See the right answer and a short explanation after each question.</p></div>
    <div><h3>Track progress</h3><p>Review your score, weak topics and streaks.</p></div>
  </div>
</div></section>

<section id="faq"><div class="wrap faqw"><div><h2>Frequently asked questions</h2><p class="sub">Quick answers before you start.</p><a href="/contact" class="btn btn-l">Contact support</a></div><div><details><summary>Is {{ $siteSettings['site_title'] }} free to use?</summary><p>Yes. Daily quizzes and subject practice are free. Create an account to save scores and streaks.</p></details><details><summary>Do I get explanations for answers?</summary><p>Yes. Every question shows the correct answer with a short explanation.</p></details><details><summary>Can I practice only one topic?</summary><p>Yes. Pick a subject, then choose a topic, or take a mixed set of all topics.</p></details><details><summary>How often are new questions added?</summary><p>New content appears when an administrator publishes chapters and quizzes.</p></details><details><summary>Can I use it on my phone?</summary><p>Yes. The site works in any mobile browser, no app needed.</p></details></div></div></section>

<section><div class="wrap">
  <div class="band">
    <div><h2>Ready for your first quiz?</h2><p>Create a free account to save scores and build a daily streak.</p></div>
    <a href="/register" class="btn btn-o" id="gb" style="height:46px;padding:0 26px">{{ __('Create free account') }}</a>
  </div>
</div></section>
@if(request()->routeIs('home'))
<section id="blogs"><div class="wrap">
  <h2>Latest blogs</h2>
  <p class="sub">Ideas and study tips for your next step.</p>
  <div class="home-blog-grid">
    @forelse($latestBlogs as $blog)
    <article class="box home-blog-card">
      <time class="daily-label" datetime="{{ $blog->published_at->toDateString() }}">{{ $blog->published_at->format('d M Y') }}</time>
      <h3><a href="{{ route('blogs.show', $blog->slug) }}">{{ $blog->title }}</a></h3>
      <p>{{ $blog->excerpt ?: Illuminate\Support\Str::limit($blog->content, 160) }}</p>
      <a class="btn btn-l" href="{{ route('blogs.show', $blog->slug) }}">Read blog →</a>
    </article>
    @empty
    <div class="box home-blog-card"><h3>New blogs are on the way</h3><p>Check back soon for study tips and learning insights.</p></div>
    @endforelse
  </div>
  <p style="margin-top:24px"><a class="btn btn-o" href="{{ route('blogs.index') }}">View all blogs</a></p>
</div></section>
@endif
</main>
