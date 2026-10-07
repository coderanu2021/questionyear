<div class="head"><div><h2>Practice quiz generation</h2><p>Generate daily, weekly and monthly quizzes for the current period.</p></div></div>
@if(session('status'))<div class="card settings-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="card settings-message" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div id="generation-response" class="card settings-message" role="status" hidden></div>
<div class="card settings-message"><h3>Automatic scheduler retained</h3><p>The scheduler checks every hour at :05 IST. Only one quiz is generated per period: 20 daily, 50 weekly and 200 monthly questions. If you generate it here first, scheduled generation skips it without calling the API again.</p><p>Manual generation uses the same question bank and Gemini API as the scheduler. Daily resets at midnight, weekly on Monday, and monthly on the first day (IST).</p></div>
@foreach($practicePeriods as $practice)
<section class="card settings-message">
    <h3>{{ ucfirst($practice['period']) }} quiz · {{ $practice['target'] }} questions</h3>
    <p>Period starts: {{ $practice['date'] }} (IST)</p>
    <p>Status: <strong>{{ $practice['ready'] ? 'Generated' : 'Pending generation' }}</strong> · {{ $practice['count'] }} / {{ $practice['target'] }} questions</p>
    @if($practice['ready'])<p>Generated at: {{ \Carbon\Carbon::parse($practice['created_at'])->setTimezone('Asia/Kolkata')->format('d M Y, H:i') }} IST. Further generation is skipped.</p>@endif
    <form method="POST" action="{{ route('admin.practice.generate') }}" data-generation-form>
        @csrf<input type="hidden" name="period" value="{{ $practice['period'] }}">
        <button class="btn pri" type="submit" @disabled($practice['ready'])>{{ $practice['ready'] ? 'Already generated' : 'Generate '.ucfirst($practice['period']).' Quiz' }}</button>
        <a class="btn" href="{{ route($practice['period']) }}">View quiz</a>
    </form>
</section>
@endforeach
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
