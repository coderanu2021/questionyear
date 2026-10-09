<div class="practice-workspace">
<div class="practice-heading"><div><span class="practice-eyebrow">LEARNING, ONE QUIZ AT A TIME</span><h2>Practice quizzes</h2><p>A little preparation. A fresh challenge for your learners.</p></div><span class="practice-date">{{ now('Asia/Kolkata')->format('d M Y') }} <span>IST</span></span></div>
@if(session('status'))<div class="practice-notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="practice-notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div id="generation-response" class="practice-notice" role="status" hidden></div>
<section class="practice-overview" aria-label="Generation overview"><div class="practice-overview-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5Z"/></svg></div><div><h3>Your next challenges, ready when you are.</h3><p>Generate a quiz now, or let the automatic schedule take care of it.</p></div><div class="practice-overview-count"><strong>{{ collect($practicePeriods)->where('ready', true)->count() }}<span> / {{ count($practicePeriods) }}</span></strong><span>quizzes ready</span></div></section>
<div class="practice-section-heading"><h3>This period’s quizzes</h3><span>Every generated set stays available</span></div>
<div class="practice-grid">
@foreach($practicePeriods as $practice)
<section @class(['practice-quiz-card', 'is-ready' => $practice['ready']])>
    <div class="practice-card-top"><span class="practice-period-icon" aria-hidden="true"><svg viewBox="0 0 24 24">@if($practice['period'] === 'daily')<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/>@elseif($practice['period'] === 'weekly')<rect x="4" y="5" width="16" height="16" rx="3"/><path d="M8 3v4m8-4v4M4 11h16m-12 4h3m2 0h3"/>@else<path d="M4 5h16v16H4zM8 3v4m8-4v4M4 11h16m-12 4h2m4 0h2m-8 3h2m4 0h2"/>@endif</svg></span><span @class(['practice-status', 'ready' => $practice['ready']])><i aria-hidden="true"></i>{{ $practice['ready'] ? 'Generated' : 'Pending generation' }}</span></div>
    <h3>{{ ucfirst($practice['period']) }} quiz</h3>
    <p class="practice-card-description">{{ match($practice['period']) { 'daily' => 'A small step forward, every day.', 'weekly' => 'Bring the week’s learning together.', default => 'A bigger challenge. A clearer picture.' } }}</p>
    <div class="practice-question-total"><strong>{{ $practice['target'] }}</strong><span>questions<br>per {{ $practice['period'] === 'daily' ? 'day' : ($practice['period'] === 'weekly' ? 'week' : 'month') }}</span></div>
    <div class="practice-progress-meta"><span>Questions prepared</span><strong>{{ $practice['count'] }} / {{ $practice['target'] }}</strong></div>
    <progress class="practice-progress" value="{{ min($practice['count'], $practice['target']) }}" max="{{ $practice['target'] }}" aria-label="{{ ucfirst($practice['period']) }} questions prepared"></progress>
    <dl class="practice-card-details"><div><dt>Period starts</dt><dd>{{ \Carbon\Carbon::parse($practice['date'])->format('d M Y') }}</dd></div><div><dt>Resets</dt><dd>{{ match($practice['period']) { 'daily' => 'Daily', 'weekly' => 'Monday', default => '1st of the month' } }} · 00:00 IST</dd></div></dl>
    <p class="practice-generated-time">{{ $practice['ready'] ? 'Prepared '.\Carbon\Carbon::parse($practice['created_at'])->setTimezone('Asia/Kolkata')->format('d M, H:i').' IST' : 'Ready to generate for this period' }}</p>
    <p class="practice-generated-time">{{ $practice['set_count'] }} saved sets this period</p>
    <form method="POST" action="{{ route('admin.practice.generate') }}" data-generation-form>
        @csrf<input type="hidden" name="period" value="{{ $practice['period'] }}">
        <button class="practice-generate-button" type="submit">{{ $practice['ready'] ? 'Generate another '.ucfirst($practice['period']).' Quiz' : 'Generate '.ucfirst($practice['period']).' Quiz' }}</button>
        <a class="practice-view-link" href="{{ $practice['set_id'] ? route($practice['period'].'.set', ['set' => $practice['set_id']]) : route($practice['period']) }}">{{ $practice['ready'] ? 'View latest quiz' : 'Preview quiz page' }} <span aria-hidden="true">↗</span></a>
        <a class="practice-view-link" href="{{ route($practice['period']) }}#quiz-archive">All saved quizzes <span aria-hidden="true">↗</span></a>
    </form>
</section>
@endforeach
</div>
<section class="practice-schedule"><div class="practice-schedule-heading"><span class="practice-schedule-dot" aria-hidden="true"></span><h3>Automatic schedule</h3><span>Enabled</span></div><p>Checked every hour at <strong>:05 IST</strong>. The scheduler prepares one quiz when a period has no sets. Generate more sets manually whenever needed; all earlier quizzes stay in the archive.</p><div class="practice-schedule-footer"><span>20 daily · 50 weekly · 200 monthly</span><span>Each set has its own quiz page</span></div></section>
<p class="practice-bottom-note">A consistent practice routine starts with a well-prepared quiz.</p>
</div>
<script>
document.querySelectorAll('[data-generation-form]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        const label = button.textContent;
        const notice = document.getElementById('generation-response');
        button.disabled = true;
        button.textContent = 'Generating…';
        notice.hidden = false;
        notice.textContent = 'Generating questions. Please wait…';
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new FormData(form)
            });
            const result = await response.json().catch(() => null);
            if (!response.ok || !result?.success) {
                const fallback = {
                    419: 'Your session expired. Refresh the page and log in again.',
                    403: 'Access denied. Log in with an active admin account.',
                    404: 'The generation endpoint was not found on this server.',
                    429: 'Too many generation requests. Wait a minute before retrying.',
                    504: 'The server timed out during generation. Refresh to check the quiz status before retrying.'
                };
                throw new Error(result?.errors?.generation?.[0] || result?.message || fallback[response.status] || `Generation failed (HTTP ${response.status}). Check the server logs.`);
            }
            notice.textContent = result.message;
            window.location.reload();
        } catch (error) {
            notice.setAttribute('role', 'alert');
            notice.textContent = error instanceof TypeError ? 'The server could not be reached or the connection was interrupted. Refresh to check the quiz status before retrying.' : error.message;
            notice.scrollIntoView({ block: 'nearest' });
        } finally {
            button.disabled = false;
            button.textContent = label;
        }
    });
});
</script>
