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
    <nav id="nav" aria-label="Main navigation"><ul>
      @foreach($navigationMenus as $menuLabel => $menuLinks)
      <li class="site-menu"><details data-site-menu><summary aria-expanded="false" aria-controls="menu-{{ $loop->index }}">{{ __($menuLabel) }}</summary><div class="site-dropdown" id="menu-{{ $loop->index }}">
        @forelse($menuLinks as $menuLink)<a href="{{ $menuLink['url'] }}" @if(request()->fullUrl() === $menuLink['url']) aria-current="page" @endif>{{ __($menuLink['label']) }}</a>@empty<p>Published resources will appear here.</p>@endforelse
      </div></details></li>
      @endforeach
    </ul></nav>
    <span class="sp"></span>
    @if(auth()->user()?->role === 'admin' && auth()->user()?->status === 'active')
      <a class="btn btn-o" href="{{ route('admin') }}">{{ __('Admin panel') }}</a>
    @endif
    <button class="theme" id="th" aria-label="{{ __('Switch theme') }}"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 3a9 9 0 0 1 0 18z" fill="currentColor"/></svg></button>
    <a href="{{ route(auth()->check() ? 'learning' : 'login') }}" class="signin" id="si">{{ __(auth()->check() ? 'My learning' : 'Sign in') }}</a>
    <div class="um" id="um" hidden><button id="umb" aria-expanded="false" aria-haspopup="true"><span class="av" id="uav"></span><span id="unm"></span><i class="fa-solid fa-chevron-down" style="font-size:11px"></i></button><div class="umd" id="umd" hidden><div class="who"><b id="uwn"></b><span id="uwe"></span></div><a href="{{ route('learning') }}">{{ __('My learning') }}</a><a href="/progress">{{ __('My progress') }}</a><a href="{{ route('learning.leaderboard') }}">{{ __('Weekly leaderboard') }}</a>@if(auth()->user()?->role === "admin")<a href="/admin">{{ __('Admin panel') }}</a>@endif<button id="lo"><i class="fa-solid fa-right-from-bracket"></i> {{ __('Log out') }}</button></div></div>
    <button class="menu-btn" id="mb" aria-label="{{ __('Open menu') }}" aria-expanded="false" aria-controls="nav"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button>
  </div>
  </header>
<link rel="stylesheet" href="{{ asset('css/site-navigation.css') }}?v={{ filemtime(public_path('css/site-navigation.css')) }}">
<script defer src="{{ asset('js/site-menu.js') }}?v={{ filemtime(public_path('js/site-menu.js')) }}"></script>
