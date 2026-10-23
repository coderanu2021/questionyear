<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    @include('website.partials.seo', ['defaultTitle' => $title.' – '.$siteSettings['site_title'], 'defaultDescription' => $notification->summary ?: 'Read this government job update and confirm all details in the linked official notice.', 'socialCards' => true])
    @include('website.partials.canonical', ['socialCards' => true])
    <link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/government-jobs.css') }}?v={{ filemtime(public_path('css/government-jobs.css')) }}">
    @include('website.partials.favicon')
    @if(($notification->notice_type ?? 'job') === 'job' && $notification->start_date && $notification->official_url && $notification->organization)
        @php
            $jobPostingSchema = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $notification->title, 'description' => $notification->summary ?: $notification->title, 'datePosted' => $notification->start_date->toDateString(), 'hiringOrganization' => ['@type' => 'Organization', 'name' => $notification->organization], 'url' => request()->url(), 'applicantLocationRequirements' => ['@type' => 'Country', 'name' => 'India'], 'sameAs' => $notification->official_url];
            if ($notification->last_date) { $jobPostingSchema['validThrough'] = $notification->last_date->toDateString(); }
        @endphp
        <script type="application/ld+json">{!! json_encode($jobPostingSchema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
</head>
<body>
@include('website.partials.header')
<main class="wrap gov-jobs-page gov-job-detail">
    <div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span><a href="{{ route('jobs.index') }}">Government jobs</a><span>/</span><b>{{ $notification->title }}</b></div>
    @php($jobBreadcrumb = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')], ['@type' => 'ListItem', 'position' => 2, 'name' => 'Government jobs', 'item' => route('jobs.index')], ['@type' => 'ListItem', 'position' => 3, 'name' => $notification->title, 'item' => request()->url()]]])
    <script type="application/ld+json">{!! json_encode($jobBreadcrumb, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <span class="daily-label">OFFICIAL GOVERNMENT NOTICE · {{ strtoupper(str_replace('-', ' ', $notification->notice_type ?? 'job')) }}</span>
    <h1>{{ $notification->title }}</h1>
    <p class="sub">{{ $notification->organization ?: 'Organization: check official notice' }} · {{ $notification->state ?: 'State: check official notice' }}</p>
    <p class="notification-checked">Source checked on: @if($notification->source_checked_at)<time datetime="{{ $notification->source_checked_at->toDateString() }}">{{ $notification->source_checked_at->timezone('Asia/Kolkata')->format('d M Y') }}</time>@else Check official notice @endif</p>
    <article class="box gov-job-detail-card">
        @if($notification->summary)<section><h2>Overview</h2><p>{{ $notification->summary }}</p></section>@endif
        <section><h2>Notice details</h2><dl class="gov-job-facts gov-job-detail-facts">
            <div><dt>Advertisement number</dt><dd>{{ $notification->advertisement_no ?: 'Check official notice' }}</dd></div>
            <div><dt>Vacancies</dt><dd>{{ $notification->vacancies ?? 'Check official notice' }}</dd></div>
            <div><dt>Qualification</dt><dd>{{ $notification->qualification_details ?: ($notification->qualification_level ? ucfirst(str_replace('-', ' ', $notification->qualification_level)) : 'Check official notice') }}</dd></div>
            <div><dt>Age limit</dt><dd>{{ $notification->age_limit ?: 'Check official notice' }}</dd></div>
            <div><dt>Application fee</dt><dd>{{ $notification->fee ?: 'Check official notice' }}</dd></div>
            <div><dt>Application starts</dt><dd>{{ $notification->start_date?->format('d M Y') ?? 'Check official notice' }}</dd></div>
            <div><dt>Last date</dt><dd>{{ $notification->last_date?->format('d M Y') ?? 'Check official notice' }}</dd></div>
            <div><dt>Status</dt><dd>{{ ucfirst($notification->status) }}</dd></div>
        </dl></section>
        <section class="gov-job-links"><a class="btn btn-o" href="{{ $notification->official_url }}" target="_blank" rel="noopener noreferrer">Official source ↗</a>@if($notification->pdf_url)<a href="{{ $notification->pdf_url }}" target="_blank" rel="noopener noreferrer">Official notice PDF ↗</a>@endif @if($notification->application_url)<a href="{{ $notification->application_url }}" target="_blank" rel="noopener noreferrer">Official application page ↗</a>@endif</section>
        <p class="notification-checked">Dates, vacancies and eligibility can change through official corrigenda. Confirm them in the linked notice before acting.</p>
    </article>
    <p><a href="{{ route('jobs.index') }}">← Browse all government notices</a></p>
</main>
@include('website.partials.footer')
<script src="{{ asset('js/navigation.js') }}"></script>
</body>
</html>
