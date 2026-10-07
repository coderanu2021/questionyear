@php
    $pageMetadata = App\PageSeo::resolve(request(), [
        'title' => $defaultTitle ?? $siteSettings['site_title'],
        'description' => $defaultDescription ?? $siteSettings['site_description'],
        'keywords' => $defaultKeywords ?? null,
    ], ['seoChapter' => $seoChapter ?? null, 'post' => $post ?? null, 'errorPage' => $errorPage ?? null]);
@endphp
<title>{{ $pageMetadata['title'] }}</title>
<meta name="description" content="{{ $pageMetadata['description'] }}">
<meta name="keywords" content="{{ $pageMetadata['keywords'] }}">
@if($preservePageTitle ?? false)<script>window.PAGE_SEO_TITLE={{ Illuminate\Support\Js::from($pageMetadata['title']) }};</script>@endif
