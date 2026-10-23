<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('website.partials.seo', ['defaultTitle' => $title.' – '.$siteSettings['site_title'], 'defaultDescription' => 'Browse government job notifications published from official sources. Confirm all details in the official notice before applying.', 'socialCards' => true])
    @include('website.partials.canonical', ['socialCards' => true])
    <link rel="stylesheet" href="{{ asset('css/website.css') }}?v={{ filemtime(public_path('css/website.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/government-jobs.css') }}?v={{ filemtime(public_path('css/government-jobs.css')) }}">
    @include('website.partials.favicon')
</head>
<body>
@include('website.partials.header')
<main class="wrap gov-jobs-page">
    <div class="crumb"><a href="{{ route('home') }}">Home</a><span>/</span><b>Government jobs</b></div>
    @php($jobsBreadcrumb = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')], ['@type' => 'ListItem', 'position' => 2, 'name' => $title, 'item' => request()->url()]]])
    <script type="application/ld+json">{!! json_encode($jobsBreadcrumb, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <span class="daily-label">QUESTIONYEAR · OFFICIAL NOTICES</span>
    <h1>{{ $title }}</h1>
    <p class="sub">Recruitment, admit card and result updates summarized from official sources. Verify every detail in the linked notice.</p>
    @include('website.partials.job-push-subscribe')

    <nav class="jobs-filters" aria-label="Government job categories">
        @foreach($categories as $filter => $label)
            <a href="{{ $filter === 'all' ? route('jobs.index') : route('jobs.category', ['category' => $filter]) }}" @if($category === $filter) aria-current="page" @endif>{{ $filter === 'all' ? 'All notices' : $label }}</a>
        @endforeach
    </nav>

    <form class="gov-jobs-filter-form" method="GET" action="{{ route('jobs.index') }}">
        <label>Search <input type="search" name="q" value="{{ request('q') }}" placeholder="Post, department or notice number"></label>
        <label>State <select name="state"><option value="">All states</option>@foreach($states as $state)<option value="{{ $state }}" @selected(request('state') === $state)>{{ $state }}</option>@endforeach</select></label>
        <label>Qualification <select name="qualification"><option value="">All levels</option>@foreach($qualifications as $value => $label)<option value="{{ $value }}" @selected(request('qualification') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Department <select name="department"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department }}" @selected(request('department') === $department)>{{ $department }}</option>@endforeach</select></label>
        <button class="btn btn-o" type="submit">Filter notices</button>
    </form>
    <div class="gov-job-directory-links">
        <strong>Browse by qualification:</strong>
        @foreach($qualifications as $level => $label)<a href="{{ route('jobs.qualification', ['level' => $level]) }}">{{ $label }}</a>@endforeach
        <strong>States:</strong>
        @foreach($states as $state)<a href="{{ route('jobs.state', ['state' => Illuminate\Support\Str::slug($state)]) }}">{{ $state }}</a>@endforeach
        <strong>Departments:</strong>
        @foreach($departments as $department)<a href="{{ route('jobs.department', ['department' => Illuminate\Support\Str::slug($department)]) }}">{{ $department }}</a>@endforeach
    </div>

    <section class="gov-jobs-list" aria-label="Published official notices">
        @forelse($notifications as $notification)
            <article class="box gov-job-card">
                <div class="gov-job-card-heading">
                    <div><div class="notification-badges"><span>{{ strtoupper(str_replace('-', ' ', $notification->notice_type ?? 'job')) }}</span><span>{{ ucfirst($notification->status) }}</span></div>
                        <h2><a href="{{ route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug]) }}">{{ $notification->title }}</a></h2>
                        <p>{{ $notification->organization ?: 'Check official notice' }} · {{ $notification->state ?: 'State: check official notice' }}</p>
                    </div>
                    <span class="gov-job-checked">Source checked on<br><time datetime="{{ $notification->source_checked_at?->toDateString() ?? '' }}">{{ $notification->source_checked_at?->timezone('Asia/Kolkata')->format('d M Y') ?? 'Check official notice' }}</time></span>
                </div>
                @if($notification->summary)<p>{{ $notification->summary }}</p>@endif
                <dl class="gov-job-facts">
                    <div><dt>Vacancies</dt><dd>{{ $notification->vacancies ?? 'Check official notice' }}</dd></div>
                    <div><dt>Qualification</dt><dd>{{ $notification->qualification_level ? ucfirst(str_replace('-', ' ', $notification->qualification_level)) : 'Check official notice' }}</dd></div>
                    <div><dt>Last date</dt><dd>{{ $notification->last_date?->format('d M Y') ?? 'Check official notice' }}</dd></div>
                </dl>
                <div class="gov-job-links"><a class="btn btn-o" href="{{ route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug]) }}">View notice</a><a href="{{ $notification->official_url }}" target="_blank" rel="noopener noreferrer">Official source ↗</a></div>
            </article>
        @empty
            <div class="box gov-job-empty"><h2>No published notices match these filters</h2><p>New notices appear after official-source verification and admin review.</p></div>
        @endforelse
    </section>
    {{ $notifications->links() }}
</main>
@include('website.partials.footer')
<script src="{{ asset('js/navigation.js') }}"></script>
</body>
</html>
