<div class="head">
    <div>
        <h2>Government job notices</h2>
        <p>Review the official notice, correct extracted fields, then publish or reject.</p>
    </div>
    @if(request('review') !== 'rejected')
        <form id="job-notices-bulk" method="POST" action="{{ route('admin.job-notices.bulk-publish') }}" onsubmit="return confirm('Publish the selected notices?')">
            @csrf
            <button class="btn pri" type="submit">Publish selected</button>
        </form>
    @endif
</div>

@if(session('status'))<div class="card job-notice-status" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="card job-notice-errors" role="alert"><strong>Please fix the highlighted values.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="job-notice-tabs" role="group" aria-label="Job notice review status">
    <a class="btn {{ request('review') !== 'rejected' ? 'pri' : '' }}" href="{{ route('admin.job-notices.index') }}">Pending review</a>
    <a class="btn {{ request('review') === 'rejected' ? 'pri' : '' }}" href="{{ route('admin.job-notices.index', ['review' => 'rejected']) }}">Rejected</a>
</div>

@forelse($jobNotifications as $jobNotice)
    <article class="card job-notice-card">
        <header class="job-notice-heading">
            <div>
                <span class="badge b-warn">{{ request('review') === 'rejected' ? 'Rejected' : 'Pending review' }}</span>
                @if($jobNotice->notice_type)<span class="badge b-info">{{ ucfirst(str_replace('-', ' ', $jobNotice->notice_type)) }}</span>@endif
                <h3>{{ $jobNotice->title }}</h3>
                <p>{{ $jobNotice->organization ?: 'Check official notice' }} · {{ $jobNotice->state ?: 'All India / state not confirmed' }}</p>
            </div>
            <div class="job-notice-confidence">
                <span>Extraction confidence</span>
                <strong>{{ $jobNotice->confidence !== null ? number_format($jobNotice->confidence * 100, 1).'%' : 'Check official notice' }}</strong>
            </div>
        </header>

        <div class="job-notice-source">
            <div><strong>Official source</strong><a href="{{ $jobNotice->official_url }}" target="_blank" rel="noopener noreferrer">Open official notice ↗</a></div>
            <div><strong>Source checked on</strong><span>@if($jobNotice->source_checked_at){{ $jobNotice->source_checked_at->timezone('Asia/Kolkata')->format('d M Y, h:i A').' IST' }}@else Check official notice @endif</span></div>
            <div><strong>Source</strong><span>{{ $jobNotice->rawNotice?->source?->name ?? 'Official source' }}</span></div>
        </div>

        @if(request('review') === 'rejected')
            <p class="job-notice-review-note"><strong>Review note:</strong> {{ $jobNotice->review_note ?: 'No reason recorded.' }}</p>
            <form method="POST" action="{{ route('admin.job-notices.restore', $jobNotice) }}">@csrf<button class="btn pri" type="submit">Return to pending review</button></form>
        @else
            <div class="job-notice-facts">
                <span><strong>Vacancies</strong>{{ $jobNotice->vacancies ?? 'Check official notice' }}</span>
                <span><strong>Qualification</strong>{{ $jobNotice->qualification_level ? ucfirst(str_replace('-', ' ', $jobNotice->qualification_level)) : 'Check official notice' }}</span>
                <span><strong>Last date</strong>{{ $jobNotice->last_date?->format('d M Y') ?? 'Check official notice' }}</span>
            </div>

            <details class="job-notice-edit">
                <summary>Edit extracted details</summary>
                <form method="POST" action="{{ route('admin.job-notices.update', $jobNotice) }}">
                    @csrf @method('PUT')
                    <div class="two">
                        <div class="f"><label for="job-title-{{ $jobNotice->id }}">Post title</label><input id="job-title-{{ $jobNotice->id }}" name="title" value="{{ old('title', $jobNotice->title) }}" maxlength="255" required></div>
                        <div class="f"><label for="job-org-{{ $jobNotice->id }}">Recruiting organization</label><input id="job-org-{{ $jobNotice->id }}" name="organization" value="{{ old('organization', $jobNotice->organization) }}" maxlength="255" placeholder="Check official notice"></div>
                    </div>
                    <div class="f"><label for="job-summary-{{ $jobNotice->id }}">Summary in your own words</label><textarea id="job-summary-{{ $jobNotice->id }}" name="summary" rows="3" maxlength="1000">{{ old('summary', $jobNotice->summary) }}</textarea></div>
                    <div class="two">
                        <div class="f"><label for="job-vacancies-{{ $jobNotice->id }}">Vacancies</label><input id="job-vacancies-{{ $jobNotice->id }}" type="number" min="0" name="vacancies" value="{{ old('vacancies', $jobNotice->vacancies) }}" placeholder="Check official notice"></div>
                        <div class="f"><label for="job-state-{{ $jobNotice->id }}">State</label><input id="job-state-{{ $jobNotice->id }}" name="state" value="{{ old('state', $jobNotice->state) }}" maxlength="100" placeholder="Check official notice"></div>
                        <div class="f"><label for="job-qualification-{{ $jobNotice->id }}">Qualification level</label><select id="job-qualification-{{ $jobNotice->id }}" name="qualification_level"><option value="">Check official notice</option>@foreach(['10th-pass', '12th-pass', 'graduate', 'postgraduate', 'diploma', 'other'] as $level)<option value="{{ $level }}" @selected(old('qualification_level', $jobNotice->qualification_level) === $level)>{{ ucfirst(str_replace('-', ' ', $level)) }}</option>@endforeach</select></div>
                        <div class="f"><label for="job-type-{{ $jobNotice->id }}">Notice type</label><select id="job-type-{{ $jobNotice->id }}" name="notice_type"><option value="">Check official notice</option>@foreach(['job', 'admit-card', 'result', 'deadline', 'update'] as $type)<option value="{{ $type }}" @selected(old('notice_type', $jobNotice->notice_type) === $type)>{{ ucfirst(str_replace('-', ' ', $type)) }}</option>@endforeach</select></div>
                        <div class="f"><label for="job-advertisement-{{ $jobNotice->id }}">Advertisement number</label><input id="job-advertisement-{{ $jobNotice->id }}" name="advertisement_no" value="{{ old('advertisement_no', $jobNotice->advertisement_no) }}" maxlength="100" placeholder="Check official notice"></div>
                        <div class="f"><label for="job-fee-{{ $jobNotice->id }}">Fee</label><input id="job-fee-{{ $jobNotice->id }}" name="fee" value="{{ old('fee', $jobNotice->fee) }}" maxlength="500" placeholder="Check official notice"></div>
                        <div class="f"><label for="job-age-{{ $jobNotice->id }}">Age limit</label><input id="job-age-{{ $jobNotice->id }}" name="age_limit" value="{{ old('age_limit', $jobNotice->age_limit) }}" maxlength="500" placeholder="Check official notice"></div>
                        <div class="f"><label for="job-start-{{ $jobNotice->id }}">Application start date</label><input id="job-start-{{ $jobNotice->id }}" type="date" name="start_date" value="{{ old('start_date', $jobNotice->start_date?->format('Y-m-d')) }}"><small>Leave blank if the notice does not confirm it.</small></div>
                        <div class="f"><label for="job-last-{{ $jobNotice->id }}">Last date</label><input id="job-last-{{ $jobNotice->id }}" type="date" name="last_date" value="{{ old('last_date', $jobNotice->last_date?->format('Y-m-d')) }}"><small>Leave blank if the notice does not confirm it.</small></div>
                        <div class="f"><label for="job-pdf-{{ $jobNotice->id }}">Official PDF URL</label><input id="job-pdf-{{ $jobNotice->id }}" type="url" name="pdf_url" value="{{ old('pdf_url', $jobNotice->pdf_url) }}" maxlength="512" placeholder="Check official notice"></div>
                        <div class="f"><label for="job-apply-{{ $jobNotice->id }}">Official application URL</label><input id="job-apply-{{ $jobNotice->id }}" type="url" name="application_url" value="{{ old('application_url', $jobNotice->application_url) }}" maxlength="512" placeholder="Check official notice"></div>
                    </div>
                    <div class="f"><label for="job-qualification-details-{{ $jobNotice->id }}">Qualification details</label><textarea id="job-qualification-details-{{ $jobNotice->id }}" name="qualification_details" rows="3" maxlength="3000">{{ old('qualification_details', $jobNotice->qualification_details) }}</textarea></div>
                    <div class="f"><label for="job-official-{{ $jobNotice->id }}">Official source URL</label><input id="job-official-{{ $jobNotice->id }}" type="url" name="official_url" value="{{ old('official_url', $jobNotice->official_url) }}" maxlength="512" required></div>
                    <div class="f"><label for="job-confidence-{{ $jobNotice->id }}">Confidence (0–1)</label><input id="job-confidence-{{ $jobNotice->id }}" type="number" step="0.0001" min="0" max="1" name="confidence" value="{{ old('confidence', $jobNotice->confidence) }}" placeholder="Check official notice"></div>
                    <button class="btn pri" type="submit">Save changes</button>
                </form>
            </details>

            <div class="job-notice-actions">
                <label><input type="checkbox" name="notification_ids[]" value="{{ $jobNotice->id }}" form="job-notices-bulk"> Select for bulk publish</label>
                <form method="POST" action="{{ route('admin.job-notices.approve', $jobNotice) }}">@csrf<button class="btn pri" type="submit">Approve and publish</button></form>
                <form method="POST" action="{{ route('admin.job-notices.reject', $jobNotice) }}">@csrf<input type="hidden" name="review_note" value="Rejected during official notice review"><button class="btn dng" type="submit">Reject</button></form>
            </div>
        @endif

        @if($jobNotice->rawNotice?->raw_text)
            <details class="job-notice-raw"><summary>View fetched notice text</summary><pre>{{ $jobNotice->rawNotice->raw_text }}</pre></details>
        @endif
    </article>
@empty
    <div class="card job-notice-empty">
        <h3>{{ request('review') === 'rejected' ? 'No rejected notices' : 'Nothing to review' }}</h3>
        <p>{{ request('review') === 'rejected' ? 'Rejected notices will appear here and can be returned to pending review.' : 'New official notices will appear here after fetching and extraction.' }}</p>
    </div>
@endforelse

@if($jobNotifications->hasPages())
    <div class="job-notice-pagination"><span>Showing {{ $jobNotifications->firstItem() ?? 0 }}–{{ $jobNotifications->lastItem() ?? 0 }} of {{ $jobNotifications->total() }}</span><nav aria-label="Job notice review pagination">@if($jobNotifications->previousPageUrl())<a class="btn" href="{{ $jobNotifications->previousPageUrl() }}">← Previous</a>@endif <span>Page {{ $jobNotifications->currentPage() }} of {{ $jobNotifications->lastPage() }}</span> @if($jobNotifications->nextPageUrl())<a class="btn" href="{{ $jobNotifications->nextPageUrl() }}">Next →</a>@endif</nav></div>
@endif
