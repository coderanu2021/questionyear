@php
    $configuredUrl = rtrim((string) config('app.url'), '/');
    $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
    $useConfiguredUrl = $configuredHost && ! in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true);
    $canonicalPath = $canonicalPath ?? request()->getPathInfo();
    $canonicalUrl = $useConfiguredUrl
        ? $configuredUrl.$canonicalPath
        : ($canonicalPath === request()->getPathInfo() ? request()->url() : request()->getSchemeAndHttpHost().$canonicalPath);
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}">
@if($socialCards ?? false)<meta property="og:url" content="{{ $canonicalUrl }}">@endif
