<div class="head">
    <div><h2>Official source terms review</h2><p>Automated fetching is paused until an admin checks each source's official terms and explicitly allows it.</p></div>
</div>

@if(session('status'))<div class="card job-source-status" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="card job-source-errors" role="alert"><strong>Please fix the highlighted values.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="card job-source-policy-note"><strong>Review before enabling</strong><p>Check the source's own terms, copyright and access rules for automated fetching, AI processing and short summaries. Keep access disabled if permission is unclear. Robots.txt is checked separately on each fetch.</p></div>

@forelse($jobSources as $jobSource)
    <article class="card job-source-card">
        <div class="job-source-title">
            <div><h3>{{ $jobSource->name }}</h3><a href="{{ $jobSource->url }}" target="_blank" rel="noopener noreferrer">Official source website ↗</a></div>
            <span class="badge {{ $jobSource->terms_allows_automated_access ? 'b-ok' : 'b-warn' }}">{{ $jobSource->terms_allows_automated_access ? 'Terms approved' : 'Fetch paused' }}</span>
        </div>
        <form method="POST" action="{{ route('admin.job-sources.update', $jobSource) }}">
            @csrf @method('PUT')
            <div class="f"><label for="job-source-terms-{{ $jobSource->id }}">Official terms or policy URL</label><input id="job-source-terms-{{ $jobSource->id }}" type="url" name="terms_url" value="{{ old('terms_url', $jobSource->terms_url) }}" maxlength="512" placeholder="Open the source website and copy its policy link"></div>
            <div class="job-source-review-meta">@if($jobSource->terms_reviewed_at)Reviewed on {{ $jobSource->terms_reviewed_at->timezone('Asia/Kolkata')->format('d M Y') }}@else Terms not reviewed @endif · Last fetch attempt: {{ $jobSource->last_run_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') ?? 'None' }}</div>
            <label class="job-source-check"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" @checked(old('active', $jobSource->active))> Source is active</label>
            <label class="job-source-check"><input type="hidden" name="terms_allows_automated_access" value="0"><input type="checkbox" name="terms_allows_automated_access" value="1" @checked(old('terms_allows_automated_access', $jobSource->terms_allows_automated_access))> I reviewed the linked official policy and allow the configured automated access and AI processing</label>
            <button class="btn pri" type="submit">Save source review</button>
        </form>
    </article>
@empty
    <div class="card">No official sources have been configured.</div>
@endforelse
