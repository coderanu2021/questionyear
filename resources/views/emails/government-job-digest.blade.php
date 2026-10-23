<h1>Government job updates</h1>
<p>Here are recently published government job notices from official sources. Read each official notice before applying.</p>
@forelse($notifications as $notification)
    <article style="margin:0 0 24px;padding-bottom:16px;border-bottom:1px solid #ddd">
        <h2><a href="{{ route('job-notices.show', ['year' => $notification->created_at->format('Y'), 'slug' => $notification->slug]) }}">{{ $notification->title }}</a></h2>
        <p>{{ $notification->organization ?: 'Check official notice' }} · Last date: {{ $notification->last_date?->format('d M Y') ?? 'Check official notice' }}</p>
        @if($notification->summary)<p>{{ $notification->summary }}</p>@endif
        <p><a href="{{ $notification->official_url }}">Official source</a> · Source checked on {{ $notification->source_checked_at?->timezone('Asia/Kolkata')->format('d M Y') ?? 'Check official notice' }}</p>
    </article>
@empty
    <p>No new notices were published in the digest period.</p>
@endforelse
<p>Always verify dates, vacancies and eligibility in the linked official notice.</p>
