<div class="head"><div><h2>Practice quiz generation</h2><p>Generate daily, weekly and monthly quizzes for the current period.</p></div></div>
@if(session('status'))<div class="card settings-message" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="card settings-message" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="card settings-message"><h3>Automatic scheduler retained</h3><p>The scheduler checks every hour at :05 IST. Only one quiz is generated per period: 20 daily, 50 weekly and 200 monthly questions. If you generate it here first, scheduled generation skips it without calling the API again.</p><p>Manual generation uses the same question bank and Gemini API as the scheduler. Daily resets at midnight, weekly on Monday, and monthly on the first day (IST).</p></div>
@foreach($practicePeriods as $practice)
<section class="card settings-message">
    <h3>{{ ucfirst($practice['period']) }} quiz · {{ $practice['target'] }} questions</h3>
    <p>Period starts: {{ $practice['date'] }} (IST)</p>
    <p>Status: <strong>{{ $practice['ready'] ? 'Generated' : 'Pending generation' }}</strong> · {{ $practice['count'] }} / {{ $practice['target'] }} questions</p>
    @if($practice['ready'])<p>Generated at: {{ \Carbon\Carbon::parse($practice['created_at'])->setTimezone('Asia/Kolkata')->format('d M Y, H:i') }} IST. Further generation is skipped.</p>@endif
    <form method="POST" action="{{ route('admin.practice.generate') }}" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Generating…';">
        @csrf<input type="hidden" name="period" value="{{ $practice['period'] }}">
        <button class="btn pri" type="submit" @disabled($practice['ready'])>{{ $practice['ready'] ? 'Already generated' : 'Generate '.ucfirst($practice['period']).' Quiz' }}</button>
        <a class="btn" href="{{ route($practice['period']) }}">View quiz</a>
    </form>
</section>
@endforeach
