<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-FEQLKLHMFN"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-FEQLKLHMFN');
</script>



<script>window.SITE_TITLE={{ Illuminate\Support\Js::from($siteSettings['site_title']) }};</script>
<header>
  <div class="wrap top">
    <a href="/" class="logo">@include('website.partials.brand-logo')</a>
    <nav id="nav"><ul>
      <li><button id="sb" aria-expanded="false" aria-controls="mega">{{ __('Subjects') }} <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg></button></li>
      <li><a href="/#popular">{{ __('MCQs') }}</a></li><li><a href="{{ route('daily') }}">{{ __('Daily MCQ') }}</a></li><li><a href="{{ route('weekly') }}">{{ __('Weekly MCQ') }}</a></li><li><a href="{{ route('monthly') }}">{{ __('Monthly MCQ') }}</a></li>
      <li><a href="{{ route('blogs.index') }}">{{ __('Blogs') }}</a></li>
      <li><a href="{{ route('news.index') }}">{{ __('News') }}</a></li>
      <li class="jobs-menu"><details><summary>{{ __('Government Jobs') }}</summary><div class="jobs-dropdown">
        <a href="{{ route('jobs.index') }}">Latest Government Jobs</a>
        @foreach(App\Http\Controllers\ExamNotificationController::CATEGORIES as $jobCategory => $jobCategoryTitle)
          @if($jobCategory !== 'all')<a href="{{ route('jobs.category', ['category' => $jobCategory]) }}">{{ $jobCategoryTitle }}</a>@endif
        @endforeach
      </div></details></li>
      <li><a href="/subject/current-affairs">{{ __('Current Affairs') }}</a></li>
      <li class="mnav" id="msi"><a href="/login">{{ __('Sign in') }}</a></li>
    </ul></nav>
    <span class="sp"></span>
    @if(auth()->user()?->role === 'admin' && auth()->user()?->status === 'active')
      <a class="btn btn-o" href="{{ route('admin') }}">{{ __('Admin panel') }}</a>
    @endif
    <button class="theme" id="th" aria-label="{{ __('Switch theme') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg></button>
    <a href="/login" class="signin" id="si">{{ __('Sign in') }}</a>
    <div class="um" id="um" hidden><button id="umb" aria-expanded="false" aria-haspopup="true"><span class="av" id="uav"></span><span id="unm"></span><i class="fa-solid fa-chevron-down" style="font-size:11px"></i></button><div class="umd" id="umd" hidden><div class="who"><b id="uwn"></b><span id="uwe"></span></div><a href="{{ route('learning') }}">{{ __('My learning') }}</a><a href="/progress">{{ __('My progress') }}</a><a href="{{ route('learning.leaderboard') }}">{{ __('Weekly leaderboard') }}</a>@if(auth()->user()?->role === "admin")<a href="/admin">{{ __('Admin panel') }}</a>@endif<button id="lo"><i class="fa-solid fa-right-from-bracket"></i> {{ __('Log out') }}</button></div></div>
    <button class="menu-btn" id="mb" aria-label="{{ __('Open menu') }}"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
  </div>
  <div class="mega" id="mega"><div class="wrap">@foreach(App\Models\Subject::all()->groupBy('category') as $category => $items)<div style="--c:#0078d4"><h4>{{ $category }}</h4>@foreach($items as $subject)<a href="{{ route('subject', $subject->slug) }}">{{ $subject->name }}</a>@endforeach</div>@endforeach</div></div></header>
