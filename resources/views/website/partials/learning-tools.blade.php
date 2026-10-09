<div class="learning-tools" data-source="{{ $source }}">
    @auth
    <button type="button" class="btn btn-l" data-bookmark="1">{{ __('Save question') }}</button>
    @if($canExplain ?? false)
    <button type="button" class="btn btn-l" data-explanation="en">{{ __('English explanation') }}</button>
    <button type="button" class="btn btn-l" data-explanation="hi">हिंदी व्याख्या</button>
    @endif
    <details><summary>{{ __('Report wrong answer') }}</summary><label>{{ __('What needs correcting?') }}<textarea data-report-reason rows="3" minlength="10" maxlength="2000" placeholder="Describe the issue and the correct answer if you know it."></textarea></label><button type="button" class="btn btn-l" data-report>{{ __('Send report') }}</button></details>
    <p data-tool-message role="status" aria-live="polite"></p>
    <p data-translated lang="hi" hidden></p>
    @else
    <a href="{{ route('login') }}">Sign in to save questions and report errors</a>
    @endauth
</div>
