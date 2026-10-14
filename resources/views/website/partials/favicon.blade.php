<link rel="icon" href="/favicon.ico">
@if($siteFaviconUrl ?? null)
<link rel="icon" href="{{ $siteFaviconUrl }}">
@else
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
@endif
