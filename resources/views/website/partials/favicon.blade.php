@if($siteFaviconUrl)
<link rel="icon" href="{{ $siteFaviconUrl }}">
@else
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon.png') }}">
<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="192x192" href="{{ asset('favicon.png') }}">
@endif
