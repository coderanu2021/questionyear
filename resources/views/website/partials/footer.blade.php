<footer>
<div class="wrap fcols">
  <div><div class="logo" style="color:#fff;margin-bottom:12px">@include('website.partials.brand-logo'){{ $siteSettings['site_title'] }}</div><p style="margin:0;max-width:34ch">{{ __($siteSettings['footer_description']) }}</p><a class="site-contact-email" href="mailto:{{ $siteSettings['contact_email'] }}">{{ $siteSettings['contact_email'] }}</a></div>
  @foreach($navigationMenus as $menuLabel => $menuLinks)
  <div><h4>{{ __($menuLabel) }}</h4>@foreach($menuLinks as $menuLink)<a href="{{ $menuLink['url'] }}">{{ __($menuLink['label']) }}</a>@endforeach</div>
  @endforeach
  <div><h4>{{ __('Company') }}</h4><a href="/about">{{ __('About us') }}</a><a href="/contact">{{ __('Contact') }}</a><a href="{{ route('feedback') }}">{{ __('Feedback') }}</a><a href="/help">{{ __('Help centre') }}</a><a href="https://www.instagram.com/questionyear2026/" target="_blank" rel="noopener noreferrer">Instagram · @questionyear2026</a></div>
</div>
<div class="wrap copy"><span>© {{ now()->year }} {{ $siteSettings['site_title'] }}. All rights reserved.</span><span><a href="{{ route('page', ['page' => 'privacy']) }}">{{ __('Privacy Policy') }}</a><a href="{{ route('page', ['page' => 'terms']) }}">{{ __('Terms & Conditions') }}</a><a href="{{ route('page', ['page' => 'cookies']) }}">{{ __('Cookies') }}</a></span></div>
</footer>
