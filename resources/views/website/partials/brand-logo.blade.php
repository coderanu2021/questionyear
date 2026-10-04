@if($siteLogoUrl)
<img class="site-logo-image" src="{{ $siteLogoUrl }}" alt="{{ $siteSettings['site_title'] }} logo" width="32" height="32">
@else
<svg width="32" height="32" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="8" fill="#0078d4"/><circle cx="15" cy="15" r="7" fill="none" stroke="#fff" stroke-width="3"/><path d="M20 20l5 5" stroke="#fff" stroke-width="3" stroke-linecap="round"/></svg>
@endif
