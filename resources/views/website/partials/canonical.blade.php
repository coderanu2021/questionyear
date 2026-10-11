@php
    $configuredUrl = rtrim((string) config('app.url'), '/');
    $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
    $useConfiguredUrl = $configuredHost && ! in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true);
    $canonicalUrl = $useConfiguredUrl ? $configuredUrl.request()->getPathInfo() : request()->url();
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}">

@if(in_array(request()->route()?->getName(), App\WebsiteLocale::ROUTES, true) && !request()->routeIs('login', 'register'))
@php
    $baseUrl = $useConfiguredUrl ? $configuredUrl : request()->getSchemeAndHttpHost();
    $englishUrl = $baseUrl.App\WebsiteLocale::englishPath(request()->getPathInfo());
    $hindiUrl = $baseUrl.App\WebsiteLocale::hindiPath(request()->getPathInfo());
@endphp
<link rel="alternate" hreflang="en" href="{{ $englishUrl }}">
<link rel="alternate" hreflang="hi" href="{{ $hindiUrl }}">
<link rel="alternate" hreflang="x-default" href="{{ $englishUrl }}">
@endif
