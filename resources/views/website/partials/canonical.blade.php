@php
    $configuredUrl = rtrim((string) config('app.url'), '/');
    $configuredHost = parse_url($configuredUrl, PHP_URL_HOST);
    $useConfiguredUrl = $configuredHost && ! in_array($configuredHost, ['localhost', '127.0.0.1', '::1'], true);
    $canonicalUrl = $useConfiguredUrl ? $configuredUrl.request()->getPathInfo() : request()->url();
@endphp
<link rel="canonical" href="{{ $canonicalUrl }}">
